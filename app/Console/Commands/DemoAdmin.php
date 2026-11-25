<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class DemoAdmin extends Command
{
    protected $signature = 'app:demo-admin';

    public function getDescription(): string
    {
        return __('demo.admin_description');
    }

    public function handle(): int
    {
        $email = env('DEMO_ADMIN_EMAIL');
        $password = env('DEMO_ADMIN_PASSWORD');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || $password === '') {
            $this->error(__('demo.admin_missing'));

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'password' => $password,
            'locale' => $user->locale ?? config('app.locale'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info(__('demo.admin_ready'));

        return self::SUCCESS;
    }
}
