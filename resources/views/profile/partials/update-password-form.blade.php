<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('app.update_password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('app.password_help') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('app.current_password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" aria-describedby="update_password_current_password-error" :aria-invalid="$errors->updatePassword->has('current_password') ? 'true' : 'false'" />
            <x-input-error id="update_password_current_password-error" :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('app.new_password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" aria-describedby="update_password_password-error" :aria-invalid="$errors->updatePassword->has('password') ? 'true' : 'false'" />
            <x-input-error id="update_password_password-error" :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('app.confirm_password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" aria-describedby="update_password_password_confirmation-error" :aria-invalid="$errors->updatePassword->has('password_confirmation') ? 'true' : 'false'" />
            <x-input-error id="update_password_password_confirmation-error" :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('app.save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p role="status"
                    class="text-sm text-gray-600"
                >{{ __('app.saved') }}</p>
            @endif
        </div>
    </form>
</section>
