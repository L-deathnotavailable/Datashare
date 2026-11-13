<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationInput;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegistrationService
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function register(RegistrationInput $input): User
    {
        if ($this->users->loadUserByIdentifier($input->email)) {
            throw new ConflictHttpException('Cette adresse e-mail est déjà utilisée.');
        }

        $user = new User($input->email);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $input->password));
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('Cette adresse e-mail est déjà utilisée.');
        }

        return $user;
    }
}
