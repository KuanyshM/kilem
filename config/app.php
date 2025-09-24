<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$publicDir = $baseDir . '/public';

return [
    'paths' => [
        'root' => $baseDir,
        'public' => $publicDir,
        'data' => $baseDir . '/storage/data',
        'uploads' => $publicDir . '/uploads',
    ],
    'app' => [
        'name' => 'RugTryOn',
    ],
];
