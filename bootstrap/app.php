<?php

use App\Application;

$config = require __DIR__ . '/../config/app.php';

$app = new Application($config);

$router = $app->router();
require __DIR__ . '/../routes/web.php';

return $app;
