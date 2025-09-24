<?php

declare(strict_types=1);

namespace App\Repositories;

class UserRepository extends JsonRepository
{
    public function all(): array
    {
        return $this->load();
    }

    public function findByEmail(string $email): ?array
    {
        foreach ($this->load() as $user) {
            if (strcasecmp($user['email'], $email) === 0) {
                return $user;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->load() as $user) {
            if ((int)$user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }

    public function create(array $data): array
    {
        $users = $this->load();
        $data['id'] = $this->nextId($users);
        $users[] = $data;
        $this->persist($users);
        return $data;
    }
}
