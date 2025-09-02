<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements UserInterface
{
    public function __construct(public readonly string $username)
    {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // Do nothing
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
