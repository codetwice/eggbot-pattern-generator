<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'HomeController@index');
Route::get('/generate/{id}', 'HomeController@generateSvg');
Route::post('/generate/{id}', 'HomeController@prepareSvg');
Route::get('/generate/{id}/download', 'HomeController@downloadSvg');
Route::get('/generators', 'HomeController@getGenerators');
Route::get('/visualizer', 'HomeController@visualizeSvg');