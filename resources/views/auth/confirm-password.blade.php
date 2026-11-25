<x-guest-layout>
    <h1 class="mb-6 text-2xl font-semibold">{{ __('app.confirm') }}</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('app.confirm_intro') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('app.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" aria-describedby="password-error" :aria-invalid="$errors->has('password') ? 'true' : 'false'" />

            <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('app.confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
