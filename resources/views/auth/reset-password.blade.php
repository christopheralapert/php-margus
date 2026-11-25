<x-guest-layout>
    <h1 class="mb-6 text-2xl font-semibold">{{ __('app.reset_password') }}</h1>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('app.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" aria-describedby="email-error" :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('app.password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" aria-describedby="password-error" :aria-invalid="$errors->has('password') ? 'true' : 'false'" />
            <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('app.confirm_password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" aria-describedby="password_confirmation-error" :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'" />

            <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('app.reset_password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
