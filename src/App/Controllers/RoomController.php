<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Services\RoomService;

class RoomController
{
    public function __construct(
        private RoomService $rooms,
        private AuthService $auth
    ) {
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->optionalAuthenticate($request->header('Authorization'));
        $payload = $request->all();
        $file = $request->file('image');
        $created = $this->rooms->store($payload, $file, $user['id'] ?? null);
        return Response::json($created, 201);
    }

    public function calibrate(Request $request): Response
    {
        $id = (int)$request->param('id');
        $payload = $request->all();
        $result = $this->rooms->calibrate($id, $payload);
        return Response::json($result);
    }
}
