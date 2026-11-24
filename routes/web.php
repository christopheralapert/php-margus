<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnlineLeafletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WizardController;
use App\Http\Middleware\EnsureChatEnabled;
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
        Route::get('/', [WizardController::class, 'home'])->name('home');
        Route::get('/l/{combo}', OnlineLeafletController::class)->name('online.show');
        Route::get('/leaflet', [WizardController::class, 'procedures'])->name('wizard.procedures');
        Route::post('/leaflet', [WizardController::class, 'store'])->name('wizard.procedures.store');
        Route::get('/leaflet/details', [WizardController::class, 'details'])->name('wizard.details');
        Route::post('/leaflet/details', [WizardController::class, 'storeDetails'])->name('wizard.details.store');
        Route::get('/leaflet/preview', [WizardController::class, 'preview'])->name('wizard.preview');
        Route::post('/leaflet/pdf', [WizardController::class, 'pdf'])->name('leaflet.pdf');

        // Chat backend (T6.1): session CSRF protection remains in the web group.
        Route::post('/chat', ChatController::class)
            ->middleware([EnsureChatEnabled::class, 'throttle:chat'])
            ->name('chat.send');

        Route::view('/dashboard', 'dashboard')
            ->middleware(['auth', 'verified'])->name('dashboard');

        Route::middleware('auth')->group(function () {
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        require __DIR__.'/auth.php';

        // Admin catalogue (T4.1).
        Route::prefix('admin')->name('admin.')
            ->middleware(['auth', 'verified', 'can:admin'])
            ->group(function () {
                Route::get('/', [CategoryController::class, 'dashboard'])->name('dashboard');
                Route::resource('categories', CategoryController::class)->except('show');
            });
    });
