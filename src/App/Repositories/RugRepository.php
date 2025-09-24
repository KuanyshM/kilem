<?php

declare(strict_types=1);

namespace App\Repositories;

class RugRepository extends JsonRepository
{
    public function paginate(int $page, int $perPage, ?bool $isActive = null): array
    {
        $items = $this->load();
        if ($isActive !== null) {
            $items = array_values(array_filter($items, static fn ($item) => (bool)$item['is_active'] === $isActive));
        }

        $total = count($items);
        $offset = max(0, ($page - 1) * $perPage);
        $paged = array_slice($items, $offset, $perPage);

        return [
            'items' => $paged,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
            ],
        ];
    }

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
