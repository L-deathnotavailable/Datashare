<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegistrationInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.')]
        #[Assert\Email(message: 'Saisissez une adresse e-mail valide.')]
        #[Assert\Length(max: 254, maxMessage: 'L’adresse e-mail est trop longue.')]
        public string $email,
        #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
        #[Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins 8 caractères.', maxMessage: 'Le mot de passe est trop long.')]
        public string $password,
    ) {}
}
