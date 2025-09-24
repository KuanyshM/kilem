<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = __DIR__ . '/App/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($relativePath)) {
        require $relativePath;
    }
});
