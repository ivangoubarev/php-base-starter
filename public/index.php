<?php
require_once '../config/bootstrap.php';

require_once '../core/Router.php';
require_once '../core/Database.php';
require_once '../core/Container.php';
require_once '../core/LoginRateLimiter.php';
require_once '../core/LogDatabase.php';
require_once '../core/Logger.php';

require_once '../controllers/UserSessionController.php';
require_once '../controllers/InicioController.php';

require_once '../repositories/UserRepository.php';

session_start();

$container = new Container();

$container->set(Database::class, function () {
    return new Database(
        host: $_ENV['DB_HOST'] ?? 'localhost',
        dbname: $_ENV['DB_NAME'] ?? '',
        username: $_ENV['DB_USER'] ?? '',
        password: $_ENV['DB_PASS'] ?? ''
    );
});

$container->set(UserRepository::class, function (Container $c) {
    return new UserRepository($c->get(Database::class));
});

$container->set(UserSessionController::class, function (Container $c) {
    return new UserSessionController($c->get(UserRepository::class), $c->get(LoginRateLimiter::class), $c->get(Logger::class));
});

$container->set(InicioController::class, function (Container $c) {
    return new InicioController($c->get(UserRepository::class));
});

$container->set(LoginRateLimiter::class, function (Container $c) {
    return new LoginRateLimiter($c->get(Database::class), 5, 15);
});

$container->set(LogDatabase::class, function (Container $c) {
    return new LogDatabase(
        host: $_ENV['LOG_DB_HOST'] ?? 'localhost',
        dbname: $_ENV['LOG_DB_NAME'] ?? '',
        username: $_ENV['LOG_DB_USER'] ?? '',
        password: $_ENV['LOG_DB_PASS'] ?? ''
    );
});

$container->set(Logger::class, function (Container $c) {
    return new Logger($c->get(LogDatabase::class));
});

$router = new Router($container);

// Definir rutas y asignarlas a un controlador y su método
$router->get('/', [InicioController::class, 'index'], true);
$router->get('/login', [UserSessionController::class, 'getLogin'], false);

$router->post('/login', [UserSessionController::class,'postLogin'], false);

// Ejecutar
$router->resolver();