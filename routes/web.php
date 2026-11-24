<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $supported = config('app.supported_locales');
    $cookie = $request->cookie('locale');
    $locale = in_array($cookie, $supported, true)
        ? $cookie
        : ($request->getPreferredLanguage($supported) ?? 'et');

    return redirect()->route('home', ['locale' => $locale]);
});

foreach (['login', 'register', 'dashboard'] as $path) {
    Route::redirect('/'.$path, '/et/'.$path);
}

Route::prefix('{locale}')
    ->whereIn('locale', config('app.supported_locales'))
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::view('/', 'home')->name('home');

        Route::view('/dashboard', 'dashboard')
            ->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware('auth')->group(function () {
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        require __DIR__.'/auth.php';
    });
