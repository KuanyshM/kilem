<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Exceptions\HttpException;
use App\Repositories\RoomPhotoRepository;
use App\Repositories\RugRepository;
use App\Repositories\TryOnRepository;

class TryOnService
{
    public function __construct(
        private TryOnRepository $tryOns,
        private RoomPhotoRepository $rooms,
        private RugRepository $rugs
    ) {
    }

    public function store(array $payload, ?int $userId): array
    {
        $roomId = isset($payload['room_photo_id']) ? (int)$payload['room_photo_id'] : 0;
        $rugId = isset($payload['rug_id']) ? (int)$payload['rug_id'] : 0;
        $room = $this->rooms->find($roomId);
        $rug = $this->rugs->find($rugId);

        if (!$room) {
            throw new HttpException(404, 'Room photo not found');
        }
        if (!$rug) {
            throw new HttpException(404, 'Rug not found');
        }

        $scaleX = (float)($payload['scale_x'] ?? 0);
        $scaleY = (float)($payload['scale_y'] ?? 0);
        if ($scaleX <= 0 || $scaleY <= 0) {
            throw new HttpException(422, 'Validation failed', ['scale' => 'Scale must be positive']);
        }

        $record = [
            'user_id' => $userId,
            'room_photo_id' => $roomId,
            'rug_id' => $rugId,
            'pos_x' => (int)($payload['pos_x'] ?? 0),
            'pos_y' => (int)($payload['pos_y'] ?? 0),
            'rotate_deg' => round((float)($payload['rotate_deg'] ?? 0), 2),
            'scale_x' => round($scaleX, 5),
            'scale_y' => round($scaleY, 5),
            'keep_aspect' => (bool)($payload['keep_aspect'] ?? true),
            'current_rug_width_cm' => round((float)($payload['current_rug_width_cm'] ?? 0), 2),
            'current_rug_length_cm' => round((float)($payload['current_rug_length_cm'] ?? 0), 2),
            'created_at' => date(DATE_ATOM),
            'updated_at' => date(DATE_ATOM),
        ];

        return $this->tryOns->create($record);
    }

    public function find(int $id): array
    {
        $tryOn = $this->tryOns->find($id);
        if (!$tryOn) {
            throw new HttpException(404, 'Try-on session not found');
        }
        return $tryOn;
    }
}
