<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email} {--revoke}';

    public function getDescription(): string
    {
        return __('admin.command.description');
    }

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error(__('admin.command.not_found'));

            return self::FAILURE;
        }

        $user->is_admin = ! $this->option('revoke');
        $user->save();
        $this->info(__($user->is_admin ? 'admin.command.granted' : 'admin.command.revoked'));

        return self::SUCCESS;
    }
}
