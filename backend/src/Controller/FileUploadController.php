<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ProblemResponse;
use App\Service\FileStorage;
use Aws\Exception\AwsException;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class FileUploadController
{
    private const MAX_BYTES = 1_000_000_000;
    private const BLOCKED_EXTENSIONS = ['exe', 'com', 'bat', 'cmd', 'msi', 'scr', 'ps1', 'vbs', 'js', 'jar', 'sh', 'php', 'phtml', 'phar', 'html', 'htm', 'svg'];
    private const BLOCKED_MIME_TYPES = ['application/x-dosexec', 'application/x-msdownload', 'application/x-msi', 'application/x-executable', 'application/x-pie-executable', 'application/x-sharedlib', 'application/x-shellscript', 'application/x-httpd-php', 'application/java-archive', 'application/javascript', 'text/javascript', 'text/html', 'image/svg+xml'];

    #[Route('/api/files', name: 'api_files_upload', methods: ['POST'])]
    public function upload(Request $request, #[CurrentUser] User $user, FileStorage $storage, Connection $database, LoggerInterface $logger): JsonResponse
    {
        if ((int) $request->headers->get('Content-Length', '0') > 1030 * 1024 * 1024) {
            return new ProblemResponse(413, 'Le fichier dépasse la limite de 1 Go.');
        }
        if (!str_starts_with(strtolower($request->headers->get('Content-Type', '')), 'multipart/form-data;')) {
            return new ProblemResponse(415, 'Utilisez le format multipart/form-data.');
        }

        $files = $request->files->all();
        $file = $files['file'] ?? null;
        if (count($files) !== 1 || !$file instanceof UploadedFile) {
            return new ProblemResponse(422, 'Envoyez un seul fichier dans le champ file.');
        }
        if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return new ProblemResponse(413, 'Le fichier dépasse la limite de 1 Go.');
        }
        if (!$file->isValid()) {
            return new ProblemResponse(422, 'Le transfert du fichier a échoué.');
        }

        $size = $file->getSize();
        if ($size > self::MAX_BYTES) {
            return new ProblemResponse(413, 'Le fichier dépasse la limite de 1 Go.');
        }
        $name = $file->getClientOriginalName();
        if ($size < 1 || $name === '' || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 255 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            return new ProblemResponse(422, 'Le fichier est vide ou son nom est invalide.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname()) ?: 'application/octet-stream';
        $extension = strtolower(pathinfo(rtrim($name, " ."), PATHINFO_EXTENSION));
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true) || in_array($mime, self::BLOCKED_MIME_TYPES, true)) {
            return new ProblemResponse(422, 'Ce format de fichier est interdit.');
        }

        $days = $request->request->all()['expiresInDays'] ?? '7';
        if (!is_string($days) || preg_match('/^[1-7]$/D', $days) !== 1) {
            return new ProblemResponse(422, 'La durée doit être un nombre entier de 1 à 7 jours.');
        }

        $id = Uuid::v7()->toRfc4122();
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $expiresAt = $now->add(new \DateInterval('P'.$days.'D'))->format(DATE_ATOM);

        try {
            $storage->put($id, $file->getPathname(), $size, $mime);
        } catch (AwsException $exception) {
            $logger->error('S3 upload failed', ['key' => $id, 'exception' => $exception]);
            return new ProblemResponse(503, 'Le stockage est momentanément indisponible.');
        }

        try {
            $database->insert('stored_file', [
                'id' => $id,
                'owner_id' => $user->getId()->toRfc4122(),
                'original_name' => $name,
                'size_bytes' => $size,
                'mime_type' => $mime,
                'storage_key' => $id,
                'share_token' => $token,
                'created_at' => $now->format(DATE_ATOM),
                'expires_at' => $expiresAt,
            ]);
        } catch (\Doctrine\DBAL\Exception $exception) {
            try {
                $storage->delete($id);
            } catch (AwsException $cleanupError) {
                $logger->error('S3 cleanup failed', ['key' => $id, 'exception' => $cleanupError]);
            }
            throw $exception;
        }

        return new JsonResponse([
            'id' => $id,
            'originalName' => $name,
            'sizeBytes' => $size,
            'mimeType' => $mime,
            'expiresAt' => $expiresAt,
            'shareUrl' => $request->getSchemeAndHttpHost().'/s/'.$token,
        ], 201);
    }
}
