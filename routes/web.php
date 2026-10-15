<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredClientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->middleware('guest')->name('register');
Route::post('/register', [RegisteredClientController::class, 'store'])->middleware(['guest', 'throttle:6,1']);

Route::get('/documents/aviso-de-privacidad.pdf', function () {
    return response()->file(public_path(config('privacy.document')), [
        'Content-Type' => 'application/pdf',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('privacy.notice');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::view('/registro/completado', 'auth.registration-complete')
        ->middleware('verified')->name('registration.complete');
});
