<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Exceptions\HttpException;
use App\Repositories\RugRepository;

class RugService
{
    public function __construct(
        private RugRepository $rugs,
        private FileStorageService $storage
    ) {
    }

    public function list(int $page, int $perPage, ?bool $isActive = null): array
    {
        return $this->rugs->paginate($page, $perPage, $isActive);
    }

    public function create(array $payload, ?array $file): array
    {
        $validated = $this->validatePayload($payload, requireImage: true);
        if (!$file) {
            throw new HttpException(400, 'Rug image is required');
        }
        $stored = $this->storage->storeUploadedFile($file, 'rugs');
        $validated['image_path'] = $stored['path'];
        $validated['image_url'] = $stored['url'];
        $validated['created_at'] = date(DATE_ATOM);
        $validated['updated_at'] = date(DATE_ATOM);

        $created = $this->rugs->create($validated);
        return $created;
    }

    public function update(int $id, array $payload, ?array $file): array
    {
        $rug = $this->rugs->find($id);
        if (!$rug) {
            throw new HttpException(404, 'Rug not found');
        }

        $validated = $this->validatePayload($payload, requireImage: false, allowPartial: true);
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $stored = $this->storage->storeUploadedFile($file, 'rugs');
            $validated['image_path'] = $stored['path'];
            $validated['image_url'] = $stored['url'];
        }

        $validated['updated_at'] = date(DATE_ATOM);

        $updated = $this->rugs->update($id, $validated);
        if (!$updated) {
            throw new HttpException(500, 'Unable to update rug');
        }
        return $updated;
    }

    private function validatePayload(array $payload, bool $requireImage, bool $allowPartial = false): array
    {
        $errors = [];
        $data = [];

        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '' && !$allowPartial) {
            $errors['title'] = 'Title is required';
        } elseif ($title !== '') {
            $data['title'] = $title;
        }

        $width = $payload['width_cm'] ?? $payload['width'] ?? null;
        if ($width === null) {
            if (!$allowPartial) {
                $errors['width_cm'] = 'Width is required';
            }
        } else {
            $width = (float)$width;
            if ($width <= 0) {
                $errors['width_cm'] = 'Width must be positive';
            } else {
                $data['width_cm'] = round($width, 2);
            }
        }

        $length = $payload['length_cm'] ?? $payload['length'] ?? null;
        if ($length === null) {
            if (!$allowPartial) {
                $errors['length_cm'] = 'Length is required';
            }
        } else {
            $length = (float)$length;
            if ($length <= 0) {
                $errors['length_cm'] = 'Length must be positive';
            } else {
                $data['length_cm'] = round($length, 2);
            }
        }

        if (isset($payload['is_active'])) {
            $data['is_active'] = (bool)$payload['is_active'];
        } elseif (!$allowPartial) {
            $data['is_active'] = true;
        }

        if (!empty($errors)) {
            throw new HttpException(422, 'Validation failed', $errors);
        }

        if ($requireImage) {
            $data['is_active'] = $data['is_active'] ?? true;
        }

        return $data;
    }
}
