<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(
            before: [AuthenticatesRequests::class, RedirectIfAuthenticated::class, SubstituteBindings::class],
            prepend: SetLocale::class,
        );
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
