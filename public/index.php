<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$request = App\Http\Request::fromGlobals();
$response = $app->handle($request);
$response->send();
