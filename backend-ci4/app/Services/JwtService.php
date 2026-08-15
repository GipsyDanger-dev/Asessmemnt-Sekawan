<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

class JwtService
{
    /** @var array<string, mixed>|null */
    private ?array $authenticatedUser = null;

    /** @param array<string, mixed> $user */
    public function issue(array $user): string
    {
        $issuedAt = time();

        return JWT::encode([
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role_code'],
            'approval_level' => $user['approval_level'] === null ? null : (int) $user['approval_level'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + (int) env('JWT_TTL', 3600),
        ], $this->secret(), 'HS256');
    }

    /** @return array<string, mixed>|null */
    public function decode(string $token): ?array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secret(), 'HS256'));
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $user */
    public function setAuthenticatedUser(array $user): void
    {
        $this->authenticatedUser = $user;
    }

    /** @return array<string, mixed>|null */
    public function authenticatedUser(): ?array
    {
        return $this->authenticatedUser;
    }

    private function secret(): string
    {
        $secret = (string) env('JWT_SECRET', '');

        if ($secret === '' || $secret === 'change-this-to-a-long-random-secret') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        return $secret;
    }
}
