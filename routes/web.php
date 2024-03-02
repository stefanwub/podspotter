<?php

use App\Services\LocalWhisperService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('server-processes/{instance}', function($instance) {
    return LocalWhisperService::processes($instance);
});

require __DIR__.'/auth.php';
