<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
});

Route::middleware('access.session')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/mis-archivos', [FolderController::class, 'mine'])
        ->middleware('access.permission:nube_mis_archivos_ver')
        ->name('folders.mine');
    Route::get('/mis-archivos/{folder}', [FolderController::class, 'mine'])
        ->middleware('access.permission:nube_mis_archivos_ver')
        ->name('folders.mine.show');
    Route::post('/mis-archivos/carpetas', [FolderController::class, 'store'])
        ->name('folders.store');
    Route::patch('/mis-archivos/carpetas/{folder}', [FolderController::class, 'update'])
        ->name('folders.update');
    Route::delete('/mis-archivos/carpetas/{folder}', [FolderController::class, 'destroy'])
        ->name('folders.destroy');
    Route::patch('/carpetas/{folder}/visibilidad', [FolderController::class, 'changeVisibility'])
        ->name('folders.visibility');
    Route::post('/mis-archivos/archivos', [FileController::class, 'store'])
        ->name('files.store');
    Route::get('/mis-archivos/archivos/{file}/descargar', [FileController::class, 'download'])
        ->name('files.download');
    Route::patch('/mis-archivos/archivos/{file}', [FileController::class, 'update'])
        ->name('files.update');
    Route::patch('/mis-archivos/archivos/{file}/mover', [FileController::class, 'move'])
        ->name('files.move');
    Route::delete('/mis-archivos/archivos/{file}', [FileController::class, 'destroy'])
        ->name('files.destroy');
    Route::patch('/archivos/{file}/visibilidad', [FileController::class, 'changeVisibility'])
        ->name('files.visibility');
    Route::get('/mi-departamento', [FolderController::class, 'department'])
        ->middleware('access.permission:nube_departamento_ver')
        ->name('folders.department');
    Route::get('/mi-departamento/{folder}', [FolderController::class, 'department'])
        ->middleware('access.permission:nube_departamento_ver')
        ->name('folders.department.show');
    Route::get('/publicos', [FolderController::class, 'public'])
        ->middleware('access.permission:nube_publicos_ver')
        ->name('folders.public');
    Route::get('/publicos/{folder}', [FolderController::class, 'public'])
        ->middleware('access.permission:nube_publicos_ver')
        ->name('folders.public.show');
    Route::get('/papelera', [FolderController::class, 'trash'])
        ->middleware('access.permission:nube_papelera_ver')
        ->name('folders.trash');
    Route::post('/papelera/archivos/{file}/restaurar', [FileController::class, 'restore'])
        ->middleware('access.permission:nube_papelera_restaurar')
        ->name('files.restore');
    Route::delete('/papelera/archivos/{file}/permanente', [FileController::class, 'forceDestroy'])
        ->name('files.force-destroy');
    Route::post('/logout', LogoutController::class)->name('logout');
});
