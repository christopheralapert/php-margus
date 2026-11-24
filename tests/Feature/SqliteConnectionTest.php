<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteConnectionTest extends TestCase
{
    public function test_memory_database_enforces_foreign_keys(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_file_database_uses_the_required_pragmas_on_every_connection(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'leaflet-sqlite-');
        $this->assertNotFalse($path);

        config(['database.connections.sqlite_file' => array_replace(
            config('database.connections.sqlite'),
            ['database' => $path, 'url' => null],
        )]);

        try {
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $connection = DB::connection('sqlite_file');
                $this->assertSame('wal', $connection->scalar('PRAGMA journal_mode'));
                $this->assertSame(1, (int) $connection->scalar('PRAGMA foreign_keys'));
                $this->assertSame(5000, (int) $connection->scalar('PRAGMA busy_timeout'));
                $this->assertSame(1, (int) $connection->scalar('PRAGMA synchronous'));
                DB::purge('sqlite_file');
            }
        } finally {
            DB::purge('sqlite_file');

            foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
