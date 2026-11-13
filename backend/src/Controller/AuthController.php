<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationInput;
use App\Entity\User;
use App\Http\ProblemResponse;
use App\Service\RegistrationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController
{
    #[Route('/api/auth/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, ValidatorInterface $validator, RegistrationService $registration, RateLimiterFactory $registrationLimiter): JsonResponse
    {
        $limit = $registrationLimiter->create($request->getClientIp() ?? 'unknown')->consume();
        if (!$limit->isAccepted()) {
            return new ProblemResponse(429, 'Trop de tentatives. Réessayez dans quelques minutes.', headers: ['Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time())]);
        }

        $data = $request->toArray();
        $input = new RegistrationInput(mb_strtolower(trim($data['email'] ?? '')), $data['password'] ?? '');
        $errors = [];
        foreach ($validator->validate($input) as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        if ($errors !== []) return new ProblemResponse(422, 'Vérifiez les informations saisies.', $errors);

        return new JsonResponse($registration->register($input)->toArray(), 201);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse($user->toArray());
    }
}
