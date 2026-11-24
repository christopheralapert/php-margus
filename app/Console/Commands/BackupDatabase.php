<?php

namespace App\Console\Commands;

use App\Services\BackupDatabase as Backupper;
use Illuminate\Console\Command;
use RuntimeException;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--to=} {--keep=14}';

    protected $description = 'Create a private compressed SQLite backup and rotate older backups';

    public function handle(Backupper $backupper): int
    {
        if (! ctype_digit((string) $this->option('keep'))) {
            $this->error(__('privacy.backup.invalid_options'));

            return self::FAILURE;
        }

        try {
            $backup = $backupper->backup($this->option('to') ?? storage_path('app/private/backups'), (int) $this->option('keep'));
            $this->info(__('privacy.backup.created', $backup));

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
