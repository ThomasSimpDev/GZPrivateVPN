<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VpnServerController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Server interactions
    Route::get('/vpn/{server}/download/{protocol?}', [VpnServerController::class, 'downloadConfig'])->name('vpn.download');
    Route::get('/vpn/{server}/ping', [VpnServerController::class, 'ping'])->name('vpn.ping');

    // Real-time server list refresh (fetches from sources.json URLs)
    Route::get('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Subscriptions
    Route::get('/subscribe', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscribe', [SubscriptionController::class, 'processPayment'])->name('subscription.process');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';