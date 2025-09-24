<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Services\RugService;

class RugController
{
    public function __construct(
        private RugService $rugs,
        private AuthService $auth
    ) {
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $perPage = max(1, min(100, (int)$request->query('per_page', 12)));
        $isActiveParam = $request->query('is_active');
        $isActive = null;
        if ($isActiveParam !== null) {
            $isActive = filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $result = $this->rugs->list($page, $perPage, $isActive);
        return Response::json($result);
    }

    public function store(Request $request): Response
    {
        $this->auth->authenticateFromHeader($request->header('Authorization'), true);
        $payload = $request->all();
        $file = $request->file('image');
        $created = $this->rugs->create($payload, $file);
        return Response::json($created, 201);
    }

    public function update(Request $request): Response
    {
        $this->auth->authenticateFromHeader($request->header('Authorization'), true);
        $id = (int)$request->param('id');
        $payload = $request->all();
        $file = $request->file('image');
        $updated = $this->rugs->update($id, $payload, $file);
        return Response::json($updated);
    }
}
