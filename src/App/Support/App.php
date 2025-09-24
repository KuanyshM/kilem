<?php

declare(strict_types=1);

namespace App\Support;

use App\Controllers\AuthController;
use App\Controllers\RugController;
use App\Controllers\RoomController;
use App\Controllers\TryOnController;
use App\Http\Router;
use App\Repositories\RoomPhotoRepository;
use App\Repositories\RugRepository;
use App\Repositories\TokenRepository;
use App\Repositories\TryOnRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\FileStorageService;
use App\Services\RugService;
use App\Services\TryOnService;
use App\Services\RoomService;

final class App
{
    private static array $container = [];
    private static bool $initialized = false;

    public static function init(array $config): void
    {
        if (self::$initialized) {
            return;
        }

        self::$container['config'] = $config;

        self::bootstrapFileSystem($config);

        $router = new Router();
        self::$container['router'] = $router;

        $dataDir = $config['paths']['data'];
        $uploadsDir = $config['paths']['uploads'];

        $userRepository = new UserRepository($dataDir . '/users.json');
        $tokenRepository = new TokenRepository($dataDir . '/tokens.json');
        $rugRepository = new RugRepository($dataDir . '/rugs.json');
        $roomRepository = new RoomPhotoRepository($dataDir . '/rooms.json');
        $tryOnRepository = new TryOnRepository($dataDir . '/tryons.json');

        self::$container['userRepository'] = $userRepository;
        self::$container['tokenRepository'] = $tokenRepository;
        self::$container['rugRepository'] = $rugRepository;
        self::$container['roomRepository'] = $roomRepository;
        self::$container['tryOnRepository'] = $tryOnRepository;

        $fileStorage = new FileStorageService(
            $uploadsDir,
            $config['paths']['public']
        );
        self::$container['fileStorage'] = $fileStorage;

        $authService = new AuthService($userRepository, $tokenRepository);
        $rugService = new RugService($rugRepository, $fileStorage);
        $roomService = new RoomService($roomRepository, $fileStorage);
        $tryOnService = new TryOnService($tryOnRepository, $roomRepository, $rugRepository);

        self::$container['authService'] = $authService;
        self::$container['rugService'] = $rugService;
        self::$container['roomService'] = $roomService;
        self::$container['tryOnService'] = $tryOnService;

        self::seedAdminUser();

        $authController = new AuthController($authService);
        $rugController = new RugController($rugService, $authService);
        $roomController = new RoomController($roomService, $authService);
        $tryOnController = new TryOnController($tryOnService, $authService);

        self::$container['authController'] = $authController;
        self::$container['rugController'] = $rugController;
        self::$container['roomController'] = $roomController;
        self::$container['tryOnController'] = $tryOnController;

        self::registerRoutes($router, $authController, $rugController, $roomController, $tryOnController);

        self::$initialized = true;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$container['config'][$key] ?? $default;
    }

    public static function router(): Router
    {
        return self::$container['router'];
    }

    public static function controller(string $name): object
    {
        return self::$container[$name];
    }

    private static function bootstrapFileSystem(array $config): void
    {
        foreach ($config['paths'] as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
            }
        }

        $subDirs = ['rooms', 'rugs'];
        foreach ($subDirs as $dir) {
            $fullPath = rtrim($config['paths']['uploads'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0775, true);
            }
        }
    }

    private static function seedAdminUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::$container['userRepository'];
        $users = $userRepository->all();
        if (!empty($users)) {
            return;
        }

        $userRepository->create([
            'email' => 'admin@example.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date(DATE_ATOM)
        ]);
    }

    private static function registerRoutes(
        Router $router,
        AuthController $authController,
        RugController $rugController,
        RoomController $roomController,
        TryOnController $tryOnController
    ): void {
        $router->post('/api/auth/login', [$authController, 'login']);
        $router->post('/api/auth/logout', [$authController, 'logout']);

        $router->get('/api/rugs', [$rugController, 'index']);
        $router->post('/api/rugs', [$rugController, 'store']);
        $router->put('/api/rugs/{id}', [$rugController, 'update']);

        $router->post('/api/rooms', [$roomController, 'store']);
        $router->post('/api/rooms/{id}/calibrate', [$roomController, 'calibrate']);

        $router->post('/api/tryon', [$tryOnController, 'store']);
        $router->get('/api/tryon/{id}', [$tryOnController, 'show']);
    }
}
