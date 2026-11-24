<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $directory;

    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/backup-'.bin2hex(random_bytes(8)));
        File::makeDirectory($this->directory, 0700, true);
        touch($this->directory.'/source.sqlite');
        $this->originalConnection = config('database.default');
        config(['database.connections.backup_test' => [
            'driver' => 'sqlite',
            'database' => $this->directory.'/source.sqlite',
            'foreign_key_constraints' => true,
        ], 'database.default' => 'backup_test']);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('backup_test');
        config(['database.default' => $this->originalConnection]);
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_backup_is_valid_gzip_sqlite_with_private_permissions_and_no_temporary_files(): void
    {
        $this->artisan('db:backup', ['--to' => $this->directory.'/backups'])->assertSuccessful();
        $files = glob($this->directory.'/backups/db-*.sqlite.gz');
        $this->assertCount(1, $files);
        $sqlite = gzdecode(file_get_contents($files[0]));
        $this->assertStringStartsWith("SQLite format 3\0", $sqlite);
        file_put_contents($this->directory.'/restored.sqlite', $sqlite);
        $restored = new PDO('sqlite:'.$this->directory.'/restored.sqlite');
        $this->assertSame('users', $restored->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn());
        $this->assertSame('ok', $restored->query('PRAGMA integrity_check')->fetchColumn());
        $restored = null;
        $this->assertSame([], glob($this->directory.'/backups/.vacuum-*'));
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertSame(0600, fileperms($files[0]) & 0777);
            $this->assertSame(0700, fileperms($this->directory.'/backups') & 0777);
        }
    }

    public function test_rotation_keeps_newest_matching_files_and_leaves_other_files_alone(): void
    {
        $directory = $this->directory.'/backups';
        File::makeDirectory($directory);
        foreach (['2020-01-01-031500', '2020-01-02-031500', '2020-01-03-031500'] as $date) {
            file_put_contents($directory.'/db-'.$date.'.sqlite.gz', 'old');
        }
        file_put_contents($directory.'/db-manual.sqlite.gz', 'keep');
        $this->artisan('db:backup', ['--to' => $directory, '--keep' => 2])->assertSuccessful();
        $this->assertFileDoesNotExist($directory.'/db-2020-01-01-031500.sqlite.gz');
        $this->assertFileDoesNotExist($directory.'/db-2020-01-02-031500.sqlite.gz');
        $this->assertFileExists($directory.'/db-2020-01-03-031500.sqlite.gz');
        $this->assertFileExists($directory.'/db-manual.sqlite.gz');
        $this->assertCount(3, glob($directory.'/db-*.sqlite.gz'));
    }

    public function test_default_directory_is_private_storage(): void
    {
        app()->useStoragePath($this->directory);
        $this->artisan('db:backup')->assertSuccessful();
        $this->assertCount(1, glob($this->directory.'/app/private/backups/db-*.sqlite.gz'));
    }

    public function test_failed_vacuum_cleans_up_temporary_files(): void
    {
        DB::beginTransaction();
        try {
            $this->artisan('db:backup', ['--to' => $this->directory.'/backups'])->assertFailed();
            $this->assertSame([], glob($this->directory.'/backups/.vacuum-*'));
            $this->assertSame([], glob($this->directory.'/backups/db-*.sqlite.gz'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_public_symlink_destination_is_refused(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Creating Windows symlinks requires privileges; run this check on Linux.');
        }
        symlink(public_path(), $this->directory.'/public-link');
        try {
            $this->artisan('db:backup', ['--to' => $this->directory.'/public-link/backup-test'])
                ->expectsOutputToContain(__('privacy.backup.public_path'))->assertFailed();
        } finally {
            unlink($this->directory.'/public-link');
        }
    }

    public function test_backup_refuses_public_paths_including_nonexistent_subdirectories(): void
    {
        foreach ([public_path(), public_path('backup-test/nested'), public_path('../public/backup-test')] as $path) {
            $this->artisan('db:backup', ['--to' => $path])
                ->expectsOutputToContain(__('privacy.backup.public_path'))->assertFailed();
        }
        $this->assertDirectoryDoesNotExist(public_path('backup-test'));
    }

    public function test_invalid_retention_and_non_sqlite_connection_fail_clearly(): void
    {
        foreach (['0', '-1', 'abc', '1.5'] as $keep) {
            $this->artisan('db:backup', ['--to' => $this->directory.'/backups', '--keep' => $keep])
                ->expectsOutputToContain(__('privacy.backup.invalid_options'))->assertFailed();
        }
        config(['database.default' => 'mysql']);
        $this->artisan('db:backup', ['--to' => $this->directory.'/backups'])
            ->expectsOutputToContain(__('privacy.backup.sqlite_only'))->assertFailed();
        $this->assertDirectoryDoesNotExist($this->directory.'/backups');
    }

    public function test_same_second_backup_does_not_overwrite_existing_backup(): void
    {
        $this->freezeTime();
        $directory = $this->directory.'/backups';
        $this->artisan('db:backup', ['--to' => $directory])->assertSuccessful();
        $filename = glob($directory.'/db-*.sqlite.gz')[0];
        $original = file_get_contents($filename);
        $this->artisan('db:backup', ['--to' => $directory])->assertFailed();
        $this->assertSame($original, file_get_contents($filename));
    }
}
