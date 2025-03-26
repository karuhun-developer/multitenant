<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'cms',
    'as' => 'cms.',
    'middleware' => ['auth', 'validate-role-permission', 'share-setting'],
], function () {

    Route::get('/', App\Livewire\Cms\Dashboard::class)->name('dashboard');

    // Tenant
    require 'tenant.php';
    // Managements
    require 'managements.php';

    // Logs
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('logs');
});
