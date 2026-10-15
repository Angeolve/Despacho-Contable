<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Preserve exact registration input so forbidden whitespace cannot be silently removed.
        $middleware->trimStrings(except: [fn (Request $request) => $request->isMethod('POST') && $request->is('register')]);
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->hasVerifiedEmail()
            ? route('registration.complete') : route('verification.notice'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
