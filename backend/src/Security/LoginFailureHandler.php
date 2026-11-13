<?php

declare(strict_types=1);

namespace App\Security;

use App\Http\ProblemResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return new ProblemResponse(429, 'Trop de tentatives. Réessayez dans une minute.', headers: ['Retry-After' => '60']);
        }
        return new ProblemResponse(401, 'Adresse e-mail ou mot de passe incorrect.', headers: ['WWW-Authenticate' => 'Bearer']);
    }
}
