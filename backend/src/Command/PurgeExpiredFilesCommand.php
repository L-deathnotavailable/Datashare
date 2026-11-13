<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\FileStorage;
use Aws\Exception\AwsException;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:files:purge-expired', description: 'Supprime les fichiers expirés et leurs métadonnées')]
final class PurgeExpiredFilesCommand extends Command
{
    public function __construct(
        private Connection $database,
        private FileStorage $storage,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->database->fetchAllAssociative(
            'SELECT id, storage_key FROM stored_file WHERE expires_at <= CURRENT_TIMESTAMP ORDER BY expires_at LIMIT 1000'
        );
        $deleted = 0;
        foreach ($files as $file) {
            try {
                $this->storage->delete($file['storage_key']);
            } catch (AwsException $exception) {
                $this->logger->error('Expired file cleanup failed', ['id' => $file['id'], 'exception' => $exception]);
                continue;
            }
            $deleted += $this->database->delete('stored_file', ['id' => $file['id']]);
        }

        $output->writeln(sprintf('%d fichier(s) expiré(s) supprimé(s).', $deleted));

        return $deleted === count($files) ? self::SUCCESS : self::FAILURE;
    }
}
