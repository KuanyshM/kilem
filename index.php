<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Http\Request;
use App\Http\StaticServer;
use App\Support\App;

$request = Request::fromGlobals();
$path = $request->path();

if (str_starts_with($path, '/api/')) {
    $response = App::router()->dispatch($request);
    $response->send();
    return;
}

StaticServer::serve($path);
