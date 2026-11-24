<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteUserData
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->savedLeaflets()->delete();
            $user->consents()->delete();
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });
    }
}
