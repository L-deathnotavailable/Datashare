<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Http\ProblemResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['validateAuthRequest', 20], KernelEvents::EXCEPTION => ['onException', -10], KernelEvents::RESPONSE => 'onResponse'];
    }

    public function validateAuthRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->isMethod('POST') || !in_array($request->getPathInfo(), ['/api/auth/register', '/api/auth/login'], true)) return;
        if ($request->getContentTypeFormat() !== 'json') throw new UnsupportedMediaTypeHttpException('Le corps de la requête doit être au format JSON.');
        if (strlen($request->getContent()) > 20000) throw new BadRequestHttpException('La requête est trop volumineuse.');
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            throw new BadRequestHttpException('Le contenu JSON est invalide.');
        }
        foreach (['email', 'password'] as $field) {
            if (array_key_exists($field, $data) && !is_string($data[$field])) throw new BadRequestHttpException('Les champs e-mail et mot de passe doivent être du texte.');
        }
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) return;
        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $detail = $status >= 500 ? 'Le service est momentanément indisponible.' : $exception->getMessage();
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $event->setResponse(new ProblemResponse($status, $detail, headers: $headers));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) return;
        $response = $event->getResponse();
        if ($response->getStatusCode() === 401 && !$response instanceof ProblemResponse) {
            $response = new ProblemResponse(401, 'Authentification requise ou identifiants incorrects.', headers: ['WWW-Authenticate' => 'Bearer']);
            $event->setResponse($response);
        }
        $response->headers->set('Cache-Control', 'no-store');
    }
}
