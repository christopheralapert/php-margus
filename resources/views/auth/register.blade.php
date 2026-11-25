<x-guest-layout>
    <h1 class="mb-6 text-2xl font-semibold">{{ __('app.register') }}</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('account.first_name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" maxlength="40" autofocus autocomplete="given-name" aria-describedby="name-error" :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
            <x-input-error id="name-error" :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('app.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" aria-describedby="email-error" :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('app.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" aria-describedby="password-error" :aria-invalid="$errors->has('password') ? 'true' : 'false'" />

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

        <div class="mt-6">
            <div class="flex items-start gap-3">
                <input id="consent" name="consent" type="checkbox" value="1" required class="mt-1 rounded border-gray-300 text-brand-primary focus:ring-brand-primary" aria-describedby="consent-error" aria-invalid="{{ $errors->has('consent') ? 'true' : 'false' }}">
                <label for="consent" class="text-sm leading-relaxed text-gray-700">
                    {{ __('account.consent') }}
                    <a href="{{ route('privacy') }}" class="rounded underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-brand-primary">{{ __('privacy.title') }}</a>
                </label>
            </div>
            <div id="consent-error"><x-input-error :messages="$errors->get('consent')" class="mt-2" /></div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('app.already_registered') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('app.register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
