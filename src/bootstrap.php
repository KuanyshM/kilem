<?php

declare(strict_types=1);

require __DIR__ . '/Autoloader.php';

use App\Support\App;

$config = require __DIR__ . '/../config/app.php';

App::init($config);
