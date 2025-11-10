<?php

use App\Http\Controllers\HomeController;

/** @var App\Routing\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/generate/{id}', [HomeController::class, 'generateSvg']);
$router->post('/generate/{id}', [HomeController::class, 'prepareSvg']);
$router->get('/generate/{id}/download', [HomeController::class, 'downloadSvg']);
$router->get('/generators', [HomeController::class, 'getGenerators']);
$router->get('/visualizer', [HomeController::class, 'visualizeSvg']);
