<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Autoloader.php';
App\Core\Autoloader::register();

// Load legacy includes if needed (until fully migrated)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$router = new App\Core\Router();
require_once __DIR__ . '/../routes/web.php';

$router->run();
