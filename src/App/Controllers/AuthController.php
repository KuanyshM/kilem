<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Exceptions\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function login(Request $request): Response
    {
        $email = trim((string)($request->input('email') ?? ''));
        $password = (string)($request->input('password') ?? '');

        if ($email === '' || $password === '') {
            throw new HttpException(422, 'Validation failed', ['email' => 'Email and password are required']);
        }

        $result = $this->auth->login($email, $password);

        return Response::json($result);
    }

    public function logout(Request $request): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s+(.*)/i', $header, $matches)) {
            throw new HttpException(401, 'Authentication required');
        }

        $token = trim($matches[1]);
        $this->auth->logout($token);

        return Response::json(['status' => 'ok']);
    }
}
