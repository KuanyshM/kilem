<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\App;

final class StaticServer
{
    public static function serve(string $path): void
    {
        $paths = App::config('paths', []);
        $publicDir = $paths['public'] ?? __DIR__ . '/../../../public';
        $fullPath = realpath($publicDir . $path);

        if ($path === '/' || !$fullPath || !str_starts_with($fullPath, $publicDir)) {
            $fullPath = $publicDir . '/index.html';
            if (!is_file($fullPath)) {
                http_response_code(404);
                echo 'Not found';
                return;
            }
            self::outputFile($fullPath);
            return;
        }

        if (is_dir($fullPath)) {
            $fullPath = rtrim($fullPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        }

        if (!is_file($fullPath)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        self::outputFile($fullPath);
    }

    private static function outputFile(string $path): void
    {
        $mime = mime_content_type($path) ?: 'text/plain';
        header('Content-Type: ' . $mime);
        readfile($path);
    }
}
