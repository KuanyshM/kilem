<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Services\TryOnService;

class TryOnController
{
    public function __construct(
        private TryOnService $tryOns,
        private AuthService $auth
    ) {
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->optionalAuthenticate($request->header('Authorization'));
        $payload = $request->all();
        $created = $this->tryOns->store($payload, $user['id'] ?? null);
        return Response::json($created, 201);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->param('id');
        $session = $this->tryOns->find($id);
        return Response::json($session);
    }
}
