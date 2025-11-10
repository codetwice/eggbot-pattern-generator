<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/generate/{id}', [HomeController::class, 'generateSvg']);
Route::post('/generate/{id}', [HomeController::class, 'prepareSvg']);
Route::get('/generate/{id}/download', [HomeController::class, 'downloadSvg']);
Route::get('/generators', [HomeController::class, 'getGenerators']);
Route::get('/visualizer', [HomeController::class, 'visualizeSvg']);
