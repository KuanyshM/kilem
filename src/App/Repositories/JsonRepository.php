<?php

declare(strict_types=1);

namespace App\Repositories;

abstract class JsonRepository
{
    public function __construct(protected string $path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_file($path)) {
            file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    protected function load(): array
    {
        $contents = file_get_contents($this->path);
        $data = json_decode($contents ?: '[]', true);
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }

    protected function persist(array $items): void
    {
        $encoded = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tempPath = $this->path . '.tmp';
        file_put_contents($tempPath, $encoded);
        rename($tempPath, $this->path);
    }

    protected function nextId(array $items): int
    {
        $max = 0;
        foreach ($items as $item) {
            $max = max($max, (int)($item['id'] ?? 0));
        }
        return $max + 1;
    }
}
