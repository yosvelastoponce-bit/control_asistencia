<?php
use App\Http\Controllers\Profesor\ProfesorLoginController;
// use App\Http\Controllers\Curso\CursoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCode\QrCodeController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Director\DirectorController;
use App\Http\Controllers\Director\ProfesorController;
use App\Http\Controllers\Director\EstudianteController;
use App\Http\Controllers\Director\GradoController;
use App\Http\Controllers\Director\SeccionController;
use App\Http\Controllers\Director\CursoController;
use App\Http\Controllers\Director\EstudianteImportController;
use App\Http\Controllers\Director\HorarioController;
use App\Http\Controllers\GeneralAttendanceController;
use App\Http\Controllers\Director\GoogleSheetController;
use App\Http\Controllers\Director\SchoolLogoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

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

Route::get('/', [GeneralAttendanceController::class, 'publicHome'])->name('home');
Route::post('/attendance/general/scan', [GeneralAttendanceController::class, 'scan'])
     ->name('attendance.general.scan');

// Redirigir si alguien entra por GET directamente
Route::get('/attendance/general/scan', fn() => redirect()->route('home'))
     ->name('attendance.general.scan.get');

Route::post('/attendance/general/scan', [GeneralAttendanceController::class, 'scan'])
     ->name('attendance.general.scan');

// Rutas para el procesamiento de ausencias
// Route::post('/attendance/process-absences', [GeneralAttendanceController::class, 'processAbsences'])
//     ->name('attendance.process-absences');
// Route::get('/attendance/statistics', [GeneralAttendanceController::class, 'getStatistics'])
//     ->name('attendance.statistics');
Route::post('/attendance/process-absences', [GeneralAttendanceController::class, 'processAbsences'])
    ->name('attendance.process-absences');
Route::get('/attendance/statistics', [GeneralAttendanceController::class, 'getStatistics'])
    ->name('attendance.statistics');

// Rutas del profesor
Route::get('/profesor/login', [ProfesorLoginController::class, 'showLogin'])->name('profesor.login');
Route::post('/profesor/login', [ProfesorLoginController::class, 'login'])->name('profesor.login.post');
Route::post('/profesor/logout', [ProfesorLoginController::class, 'logout'])->name('profesor.logout');

Route::get('/general-attendance', [GeneralAttendanceController::class, 'index'])->name('general-attendance.index');
Route::post('/general-attendance/logout', [ProfesorLoginController::class, 'logout'])->name('general-attendance.logout');

// Rutas protegidas — solo si está logueado como profesor
Route::middleware('auth:app_user')->group(function () {
    Route::resource('profesor', ProfesorLoginController::class)->except(['show']);
    Route::get('/profesor/qr-codes', [QrCodeController::class, 'generateQr'])->name('profesor.qr');
    Route::get('/attendance/scan', fn() => redirect()->route('profesor.index'))->name('attendance.scan.get');
    Route::post('/attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('/director/horarios', [HorarioController::class, 'store'])->name('director.horarios.store');
    Route::delete('/director/horarios/{id}', [HorarioController::class, 'destroy'])->name('director.horarios.destroy');
});

// Rutas públicas (registro y login)
// Rutas públicas (registro y login)
Route::get('/director/register', [DirectorController::class, 'showRegister'])->name('director.register');
Route::post('/director/register', [DirectorController::class, 'register'])->name('director.register.post');

Route::get('/director/login', [DirectorController::class, 'showLogin'])->name('director.login');
Route::post('/director/login', [DirectorController::class, 'login'])->name('director.login.post');

Route::middleware('auth:app_user')->group(function () {

    // Director
    Route::post('/director/logout',          [DirectorController::class, 'logout'])->name('director.logout');
    Route::get('/director/dashboard',        [DirectorController::class, 'dashboard'])->name('director.dashboard');
    Route::get('/director/qr-codes',         [QrCodeController::class, 'generateQr'])->name('director.qr');

    // Grados, Secciones, Cursos (CRUD simple)
    Route::apiResource('director/grados',    GradoController::class)->names('director.grados');
    Route::apiResource('director/secciones', SeccionController::class)->names('director.secciones');
    Route::apiResource('director/cursos',    CursoController::class)->names('director.cursos');

    //Cargar datos masivos Estudiantes
    Route::post('/director/estudiantes/import', [EstudianteImportController::class, 'import'])
     ->name('director.estudiantes.import');

    // Profesores y Estudiantes
    Route::post('/director/profesores',         [ProfesorController::class, 'store'])->name('director.profesores.store');
    Route::delete('/director/profesores/{id}',  [ProfesorController::class, 'destroy'])->name('director.profesores.destroy');
    Route::patch('/director/profesores/{id}/attendance-permission', [ProfesorController::class, 'updateAttendancePermission'])
        ->name('director.profesores.attendance-permission');

    Route::post('/director/estudiantes',        [EstudianteController::class, 'store'])->name('director.estudiantes.store');
    Route::delete('/director/estudiantes/{id}', [EstudianteController::class, 'destroy'])->name('director.estudiantes.destroy');

    // Google Sheets y configuración
    Route::post('/director/google-sheet', [GoogleSheetController::class, 'update'])->name('director.google-sheet.update');

    Route::post('/director/entry-schedule', [GoogleSheetController::class, 'updateSchedule'])->name('director.entry-schedule.update');
    Route::get('/director/entry-schedule', fn() => redirect()->route('director.dashboard'))->name('director.entry-schedule.get');

    // Procesar ausencias manualmente desde el dashboard
    Route::post('/director/process-absences', [GoogleSheetController::class, 'processAbsences'])->name('director.process.absences');
    // Obtener estadísticas de asistencia
    Route::get('/director/attendance-stats', [GoogleSheetController::class, 'getAttendanceStats'])->name('director.attendance.stats');

    //Logo
    Route::post('/director/logo', [SchoolLogoController::class, 'upload'])->name('director.logo.upload');
    Route::delete('/director/logo', [SchoolLogoController::class, 'destroy'])->name('director.logo.destroy');
});

// Route::resource('curso', CursoController::class);
require __DIR__.'/auth.php';
