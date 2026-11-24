<?php

namespace App\Services;

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

final class PurgeExpiredData
{
    public function __construct(private readonly Settings $settings, private readonly DeleteUserData $deleteUserData) {}

    public function purge(bool $dryRun): array
    {
        return DB::transaction(function () use ($dryRun): array {
            $withdrawn = Consent::whereNotNull('withdrawn_at');
            $accountIds = (clone $withdrawn)->where('purpose', ConsentPurpose::Account)->whereNotNull('user_id')->select('user_id');
            $users = User::where(fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('email_verified_at')->where('created_at', '<', now()->subDays((int) $this->settings->get('retention.unverified_account_days', 30))))
                ->orWhereIn('id', $accountIds));
            $leaflets = SavedLeaflet::whereHas('user', fn ($query) => $query->whereRaw('COALESCE(last_active_at, created_at) < ?', [now()->subMonthsNoOverflow((int) $this->settings->get('retention.saved_leaflet_months', 12))]));
            $personalised = SavedLeaflet::whereIn('user_id', (clone $withdrawn)->where('purpose', ConsentPurpose::PersonalisedLeaflet)->whereNotNull('user_id')->select('user_id'))
                ->where(fn ($query) => $query->whereNotNull('first_name')->orWhereNotNull('procedure_date'));
            $sessions = DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->timestamp);

            // Categories are independent candidate counts; cascaded rows may overlap.
            $counts = [
                'inactive_leaflets' => (clone $leaflets)->count(),
                'unverified_accounts' => User::whereNull('email_verified_at')->where('created_at', '<', now()->subDays((int) $this->settings->get('retention.unverified_account_days', 30)))->count(),
                'withdrawn_accounts' => User::whereIn('id', $accountIds)->count(),
                'anonymised_leaflets' => (clone $personalised)->count(),
                'withdrawn_consents' => (clone $withdrawn)->count(),
                'expired_sessions' => (clone $sessions)->count(),
            ];

            if (! $dryRun) {
                $personalised->update(['first_name' => null, 'procedure_date' => null]);
                $leaflets->delete();
                $users->eachById(fn (User $user) => $this->deleteUserData->delete($user));
                $withdrawn->delete();
                $sessions->delete();
            }

            return $counts;
        });
    }
}
