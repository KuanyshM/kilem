<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exceptions\HttpException;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                $params[$key] = $value;
            }

            try {
                $response = ($route['handler'])($request->withParams($params));
                if (!$response instanceof Response) {
                    throw new \RuntimeException('Route handler must return a Response instance.');
                }
                return $response;
            } catch (HttpException $exception) {
                return Response::json([
                    'error' => [
                        'code' => $exception->getCode() ?: $exception->getStatus(),
                        'message' => $exception->getMessage(),
                        'details' => $exception->getDetails(),
                    ],
                ], $exception->getStatus());
            } catch (\Throwable $throwable) {
                return Response::json([
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => 'Internal server error',
                    ],
                ], 500);
            }
        }

        return Response::json([
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Endpoint not found',
            ],
        ], 404);
    }
}
