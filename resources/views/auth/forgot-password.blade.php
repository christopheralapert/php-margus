<x-guest-layout>
    <h1 class="mb-6 text-2xl font-semibold">{{ __('app.forgot_password') }}</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('app.forgot_intro') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('app.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus aria-describedby="email-error" :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('app.email_reset_link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
