<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        // TODO: check that a user with same username does not exist, create new user and persist
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with
          $existing = $this->users->findByUsername($username);
    if ($existing !== null) {
        throw new \RuntimeException('Username already exists.');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $user = new User(
        id: null,
        username: $username,
        passwordHash: $hashedPassword,
        createdAt: new \DateTimeImmutable()
    );

    $this->users->save($user);

    return $user;
   
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sur ethe user exists and the password matches
        // TODO: don't forget to store in session user data needed afterwards

          $user = $this->users->findByUsername($username);

    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user->getPasswordHash())) {
        return false;
    }

    // ✅ Start session and store user ID
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['username'] = $user->getUsername();

    return true;
    }
}
