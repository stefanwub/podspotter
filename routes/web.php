<?php

use App\Http\Controllers\ProfileController;
use App\Services\LocalWhisperService;
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
        $username = config('services.ssh.username');
        $host = config('services.ssh.host');

        $privateKey = PublicKeyLoader::load(file_get_contents(config('services.ssh.key_path')));

        $ssh = new SSH2($host);

        if (! $ssh->login($username, $privateKey)) {
            return [
                'new',
                $host,
                $username,
                $privateKey,
                'error' => true,
                'error_message' => 'Login failed'
            ];
        }

        return $ssh->exec('/opt/conda/bin/python /home/info/whisper.py');
    } catch(Exception $e) {
        return [
            'new',
            $host,
            $username,
            $privateKey,
            $e->__toString()
        ];

    }
});

Route::get('envs', function() {
    return Dotenv\Dotenv::createArrayBacked(base_path())->load();
});


Route::get('server-processes', function() {
    return LocalWhisperService::processes();
});

require __DIR__.'/auth.php';
