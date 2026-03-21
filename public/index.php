<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Autoloader.php';
App\Core\Autoloader::register();

// Bootstrap: DB (loads Cache + platform_settings), Auth, CSRF
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers/Auth.php';
require_once __DIR__ . '/../includes/helpers/CSRF.php';

$router = new App\Core\Router();
require_once __DIR__ . '/../routes/web.php';

$router->run();
