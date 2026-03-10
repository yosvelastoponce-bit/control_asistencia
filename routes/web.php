<?php
use App\Http\Controllers\Profesor\ProfesorLoginController;
use App\Http\Controllers\Curso\CursoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/Index', function(){
    return Inertia::render('Profesor/Index');
})->name('index_profesor');

// Rutas del profesor
Route::get('/profesor/login', [ProfesorLoginController::class, 'showLogin'])->name('profesor.login');
Route::post('/profesor/login', [ProfesorLoginController::class, 'login'])->name('profesor.login.post');
Route::post('/profesor/logout', [ProfesorLoginController::class, 'logout'])->name('profesor.logout');

Route::resource('profesor', ProfesorLoginController::class)->except(['show']);

// Rutas cursos

Route::resource('curso', CursoController::class);
require __DIR__.'/auth.php';
