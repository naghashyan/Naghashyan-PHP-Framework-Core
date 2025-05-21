<?php
namespace sd\managers;

use ngs\AbstractManager;
use sd\dal\mappers\UserMapper;

class UserManager extends AbstractManager
{
    private UserMapper $mapper;

    public function __construct()
    {
        $this->mapper = new UserMapper();
    }

    public function getUserByUsername(string $username)
    {
        return $this->mapper->getUserByUsername($username);
    }

    public function verifyCredentials(string $username, string $password): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) {
            return false;
        }
        return password_verify($password, $user->getPasswordHash());
    }
}
