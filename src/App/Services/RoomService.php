<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Exceptions\HttpException;
use App\Repositories\RoomPhotoRepository;

class RoomService
{
    public function __construct(
        private RoomPhotoRepository $rooms,
        private FileStorageService $storage
    ) {
    }

    public function store(array $payload, ?array $file, ?int $userId): array
    {
        $width = (float)($payload['real_room_width_cm'] ?? 0);
        if ($width <= 0) {
            throw new HttpException(422, 'Validation failed', ['real_room_width_cm' => 'Width must be positive']);
        }

        if (!$file) {
            throw new HttpException(400, 'Room image is required');
        }

        $stored = $this->storage->storeUploadedFile($file, 'rooms');

        $record = [
            'user_id' => $userId,
            'image_path' => $stored['path'],
            'image_url' => $stored['url'],
            'real_room_width_cm' => round($width, 2),
            'calib_x1' => null,
            'calib_y1' => null,
            'calib_x2' => null,
            'calib_y2' => null,
            'px_per_cm' => null,
            'created_at' => date(DATE_ATOM),
        ];

        return $this->rooms->create($record);
    }

    public function calibrate(int $id, array $payload): array
    {
        $room = $this->rooms->find($id);
        if (!$room) {
            throw new HttpException(404, 'Room photo not found');
        }

        $x1 = isset($payload['calib_x1']) ? (int)$payload['calib_x1'] : null;
        $y1 = isset($payload['calib_y1']) ? (int)$payload['calib_y1'] : null;
        $x2 = isset($payload['calib_x2']) ? (int)$payload['calib_x2'] : null;
        $y2 = isset($payload['calib_y2']) ? (int)$payload['calib_y2'] : null;

        if ($x1 === null || $y1 === null || $x2 === null || $y2 === null) {
            throw new HttpException(422, 'Validation failed', ['calibration' => 'All calibration points are required']);
        }

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $distance = sqrt(($dx * $dx) + ($dy * $dy));
        if ($distance <= 0.0) {
            throw new HttpException(422, 'Validation failed', ['calibration' => 'Points must not overlap']);
        }

        $realWidth = (float)($room['real_room_width_cm'] ?? 0);
        if ($realWidth <= 0) {
            throw new HttpException(422, 'Room width must be set before calibration');
        }

        $pxPerCm = $distance / $realWidth;

        $updated = $this->rooms->update($id, [
            'calib_x1' => $x1,
            'calib_y1' => $y1,
            'calib_x2' => $x2,
            'calib_y2' => $y2,
            'px_per_cm' => round($pxPerCm, 6),
        ]);

        if (!$updated) {
            throw new HttpException(500, 'Unable to update calibration');
        }

        return [
            'room_photo_id' => $id,
            'px_per_cm' => $updated['px_per_cm'],
            'calibration_distance_px' => round($distance, 2),
        ];
    }
}
