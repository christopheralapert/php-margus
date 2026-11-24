<?php

namespace App\Console\Commands;

use App\Services\PurgeExpiredData as Purger;
use Illuminate\Console\Command;

class PurgeExpiredData extends Command
{
    protected $signature = 'leaflets:purge {--dry-run}';

    protected $description = 'Purge expired patient data and withdrawn consents';

    public function handle(Purger $purger): int
    {
        foreach ($purger->purge((bool) $this->option('dry-run')) as $category => $count) {
            $this->line(__('privacy.purge.'.$category).': '.$count);
        }

        return self::SUCCESS;
    }
}
