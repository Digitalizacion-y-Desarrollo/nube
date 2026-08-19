<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminFileController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTrashController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
});

Route::middleware('access.session')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/buscar', SearchController::class)->name('search');
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
    Route::patch('/mis-archivos/carpetas/{folder}/mover', [FolderController::class, 'move'])
        ->name('folders.move');
    Route::delete('/mis-archivos/carpetas/{folder}', [FolderController::class, 'destroy'])
        ->name('folders.destroy');
    Route::patch('/carpetas/{folder}/visibilidad', [FolderController::class, 'changeVisibility'])
        ->name('folders.visibility');
    Route::post('/mis-archivos/archivos', [FileController::class, 'store'])
        ->name('files.store');
    Route::get('/mis-archivos/archivos/{file}/descargar', [FileController::class, 'download'])
        ->name('files.download');
    Route::get('/mis-archivos/archivos/{file}/vista-previa', [FileController::class, 'preview'])
        ->name('files.preview');
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
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/perfil/foto', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::post('/perfil/foto', [ProfileController::class, 'updateAvatar'])
        ->name('profile.avatar.update');
    Route::delete('/perfil/foto', [ProfileController::class, 'destroyAvatar'])
        ->name('profile.avatar.destroy');
    Route::get('/notificaciones/{notification}/abrir', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminController::class)
    ->group(function (): void {
        Route::get('/', 'dashboard')->name('dashboard');
    });

Route::prefix('admin/configuracion')
    ->name('admin.settings')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminSettingsController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('');
        Route::post('/verificar-accesos', 'check')->name('.check');
    });

Route::prefix('admin/auditoria')
    ->name('admin.audit')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminAuditController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('');
        Route::get('/{log}', 'show')->name('.show');
    });

Route::prefix('admin/papelera')
    ->name('admin.trash')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminTrashController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('');

        Route::middleware('admin.permission')->group(function (): void {
            Route::post('/archivos/{file}/restaurar', 'restoreFile')->name('.files.restore');
            Route::delete('/archivos/{file}', 'purgeFile')->name('.files.purge');
            Route::post('/carpetas/{folder}/restaurar', 'restoreFolder')->name('.folders.restore');
            Route::delete('/carpetas/{folder}', 'purgeFolder')->name('.folders.purge');
        });
    });

Route::prefix('admin/usuarios')
    ->name('admin.users')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminUserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('');
        Route::get('/{user}', 'show')->name('.show');
    });

Route::prefix('admin/departamentos')
    ->name('admin.')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminDepartmentController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('departments');
        Route::get('/{department}', 'show')->name('departments.show');
    });

Route::prefix('admin/archivos')
    ->name('admin.')
    ->middleware(['access.session', 'superuser'])
    ->controller(AdminFileController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('files');

        Route::middleware('admin.permission')->group(function (): void {
            Route::get('/{file}/descargar', 'download')->name('files.download');
            Route::get('/{file}/vista-previa', 'preview')->name('files.preview');
            Route::patch('/{file}/visibilidad', 'changeVisibility')->name('files.visibility');
            Route::delete('/{file}', 'destroy')->name('files.destroy');
        });

        Route::get('/{file}', 'show')->name('files.show');
    });
