<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ProblemResponse extends JsonResponse
{
    public function __construct(int $status, string $detail, array $errors = [], array $headers = [])
    {
        $data = [
            'type' => 'about:blank',
            'title' => self::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
        ];
        if ($errors !== []) $data['errors'] = $errors;
        parent::__construct($data, $status, array_merge($headers, ['Content-Type' => 'application/problem+json', 'Cache-Control' => 'no-store']));
    }
}
