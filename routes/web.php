<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('phpmyinfo', function () {
    phpinfo(); 
})->name('phpmyinfo');

Route::get('key', function() {
    try {
        $username = env('SSH_USERNAME');

        $privateKey = PublicKeyLoader::load(file_get_contents(base_path('.ssh/id_rsa')));

        $ssh = new SSH2(env('SSH_HOST'), '22');

        if (! $ssh->login($username, $privateKey)) {
            return [
                env('SSH_HOST'),
                $username,
                $privateKey,
                'error' => true,
                'error_message' => 'Login failed'
            ];
        }

        return $ssh->exec('/opt/conda/bin/python /home/info/whisper.py');
    } catch(Exception $e) {
        return $e->__toString();
    }
});

require __DIR__.'/auth.php';
