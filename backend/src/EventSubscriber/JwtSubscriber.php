<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class JwtSubscriber implements EventSubscriberInterface
{
    public function __construct(#[Autowire('%lexik_jwt_authentication.token_ttl%')] private int $tokenTtl) {}

    public static function getSubscribedEvents(): array
    {
        return [Events::AUTHENTICATION_SUCCESS => 'onSuccess'];
    }

    public function onSuccess(AuthenticationSuccessEvent $event): void
    {
        $event->setData(['accessToken' => $event->getData()['token'], 'tokenType' => 'Bearer', 'expiresIn' => $this->tokenTtl]);
    }
}
