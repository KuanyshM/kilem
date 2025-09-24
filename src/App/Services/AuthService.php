<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Exceptions\HttpException;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private TokenRepository $tokens
    ) {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new HttpException(401, 'Invalid credentials', ['email' => 'Invalid email or password']);
        }

        $token = $this->generateToken();
        $this->tokens->create((int)$user['id'], $token, date(DATE_ATOM));

        return [
            'token' => $token,
            'user' => $this->exposeUser($user),
        ];
    }

    public function logout(string $token): void
    {
        $this->tokens->delete($token);
    }

    public function authenticateFromHeader(?string $header, bool $adminOnly = false): array
    {
        if (!$header || !preg_match('/Bearer\s+(.*)/i', $header, $matches)) {
            throw new HttpException(401, 'Authentication required');
        }

        $token = trim($matches[1]);
        $record = $this->tokens->find($token);
        if (!$record) {
            throw new HttpException(401, 'Invalid or expired token');
        }

        $user = $this->users->findById((int)$record['user_id']);
        if (!$user) {
            throw new HttpException(401, 'User no longer exists');
        }

        if ($adminOnly && ($user['role'] ?? '') !== 'admin') {
            throw new HttpException(403, 'Admin privileges required');
        }

        return $this->exposeUser($user) + ['token' => $token];
    }

    public function optionalAuthenticate(?string $header): ?array
    {
        if (!$header || !preg_match('/Bearer\s+(.*)/i', $header, $matches)) {
            return null;
        }

        $token = trim($matches[1]);
        $record = $this->tokens->find($token);
        if (!$record) {
            return null;
        }

        $user = $this->users->findById((int)$record['user_id']);
        if (!$user) {
            return null;
        }

        return $this->exposeUser($user) + ['token' => $token];
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function exposeUser(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
