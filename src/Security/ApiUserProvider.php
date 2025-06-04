<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserInterface as TUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<TUser>
 */
class ApiUserProvider implements UserProviderInterface
{
    private array $users = [];

    public function __construct(array $apikeys = [])
    {
        foreach ($apikeys as $apikey) {
            $this->users[$apikey['apikey']] = new ApiUser($apikey['username']);
        }
    }

    public function refreshUser(TUser $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return ApiUser::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): TUser
    {
        if (array_key_exists($identifier, $this->users)) {
            return $this->users[$identifier];
        }

        throw new UserNotFoundException();
    }
}
