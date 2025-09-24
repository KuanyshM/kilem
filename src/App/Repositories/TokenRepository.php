<?php

declare(strict_types=1);

namespace App\Repositories;

class TokenRepository extends JsonRepository
{
    public function create(int $userId, string $token, string $createdAt): void
    {
        $tokens = $this->load();
        $tokens[] = [
            'token' => $token,
            'user_id' => $userId,
            'created_at' => $createdAt,
        ];
        $this->persist($tokens);
    }

    public function find(string $token): ?array
    {
        foreach ($this->load() as $item) {
            if (hash_equals($item['token'], $token)) {
                return $item;
            }
        }
        return null;
    }

    public function delete(string $token): void
    {
        $tokens = array_filter($this->load(), static fn ($item) => !hash_equals($item['token'], $token));
        $this->persist(array_values($tokens));
    }
}
