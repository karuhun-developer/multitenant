<?php

use Illuminate\Support\Facades\Route;

// Tenant
Route::get('/tenant', App\Livewire\Cms\Tenant::class)->name('tenant');
Route::get('/tenant/user/{tenant}', App\Livewire\Cms\Tenant\User::class)->name('tenant.user');
