<?php

declare(strict_types=1);

namespace App\Repositories;

class RoomPhotoRepository extends JsonRepository
{
    public function create(array $data): array
    {
        $items = $this->load();
        $data['id'] = $this->nextId($items);
        $items[] = $data;
        $this->persist($items);
        return $data;
    }

    public function update(int $id, array $payload): ?array
    {
        $items = $this->load();
        foreach ($items as &$item) {
            if ((int)$item['id'] === $id) {
                $item = array_merge($item, $payload);
                $this->persist($items);
                return $item;
            }
        }
        return null;
    }

    public function find(int $id): ?array
    {
        foreach ($this->load() as $item) {
            if ((int)$item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }
}
