<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ConsentPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'locale' => app()->getLocale(),
            ]);
            $user->consents()->create([
                'purpose' => ConsentPurpose::Account,
                'text_version' => config('leaflet.consent_version'),
                'locale' => app()->getLocale(),
                'given_at' => now(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $user->forceFill(['last_active_at' => now()])->save();
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}
