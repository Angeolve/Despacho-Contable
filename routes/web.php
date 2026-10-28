<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredClientController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:30,1');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [RegisteredClientController::class, 'store'])->middleware('throttle:6,1');

    Route::middleware('cache.headers:no_store;private')->group(function () {
        Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
            ->middleware('throttle:6,1,password-email:')->name('password.email');
        Route::view('/reset-password/{token}', 'auth.reset-password')
            ->where('token', '[a-f0-9]{64}')->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->middleware('throttle:6,1,password-reset:')->name('password.update');
    });
});

Route::get('/documents/aviso-de-privacidad.pdf', function () {
    return response()->file(public_path(config('privacy.document')), [
        'Content-Type' => 'application/pdf',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('privacy.notice');

Route::middleware(['auth', 'auth.session', 'cache.headers:no_store;private'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::view('/registro/completado', 'auth.registration-complete')
        ->middleware(['verified', 'role:'.Role::CLIENTE])->name('registration.complete');

    Route::middleware(['verified', 'role:'.Role::CLIENTE])->group(function () {
        Route::view('/cliente', 'client.home')->name('client.home');
        Route::view('/cliente/perfil', 'client.profile')->name('client.profile');
        Route::view('/cliente/mis-servicios', 'client.coming-soon', [
            'title' => 'Mis servicios', 'icon' => 'services',
            'heading' => 'Tus servicios, en un mismo lugar',
            'description' => 'Aquí podrás consultar tus servicios y dar seguimiento a su estado cuando se habilite la contratación en la plataforma.',
        ])->name('client.services');
        Route::view('/cliente/calculadora', 'client.coming-soon', [
            'title' => 'Calculadora fiscal', 'icon' => 'calculator',
            'heading' => 'Una herramienta para orientarte',
            'description' => 'Estamos preparando la calculadora preliminar de impuestos para docentes de secundaria. Todavía no está habilitada.',
        ])->name('client.calculator');
        Route::view('/cliente/resultados', 'client.coming-soon', [
            'title' => 'Mis resultados', 'icon' => 'results',
            'heading' => 'Un espacio para tus resultados',
            'description' => 'Cuando esté disponible la calculadora, aquí podrás consultar los resultados y documentos que se generen. Por ahora, no hay resultados disponibles en esta sección.',
        ])->name('client.results');
    });
    Route::view('/administrador', 'auth.private-home', ['title' => 'Espacio de administrador'])
        ->middleware(['verified', 'role:'.Role::ADMINISTRADOR])->name('admin.home');
});
