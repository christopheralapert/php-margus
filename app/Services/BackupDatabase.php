<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BackupDatabase
{
    public function backup(string $directory, int $keep): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException(__('privacy.backup.sqlite_only'));
        }

        if ($keep < 1 || trim($directory) === '') {
            throw new RuntimeException(__('privacy.backup.invalid_options'));
        }

        $directory = $this->resolvePath($directory);
        $public = $this->resolvePath(public_path());
        $compare = PHP_OS_FAMILY === 'Windows' ? strtolower($directory) : $directory;
        $public = PHP_OS_FAMILY === 'Windows' ? strtolower($public) : $public;
        if ($compare === $public || str_starts_with($compare, $public.'/')) {
            throw new RuntimeException(__('privacy.backup.public_path'));
        }

        $oldMask = umask(0077);
        $temporary = $filename = null;
        $input = $output = null;

        try {
            if ((! is_dir($directory) && ! mkdir($directory, 0700, true)) || ! chmod($directory, 0700)) {
                throw new RuntimeException;
            }

            $temporary = tempnam($directory, '.vacuum-');
            if ($temporary === false || ! chmod($temporary, 0600)) {
                throw new RuntimeException;
            }

            DB::connection()->getPdo()->exec('VACUUM INTO '.DB::connection()->getPdo()->quote($temporary));
            $target = $directory.'/db-'.now()->format('Y-m-d-His').'.sqlite.gz';
            // Exclusive creation also prevents overwriting a backup from the same second.
            $output = fopen($target, 'xb');
            if ($output === false) {
                throw new RuntimeException;
            }
            $filename = $target;
            if (! chmod($filename, 0600)) {
                throw new RuntimeException;
            }
            $filter = stream_filter_append($output, 'zlib.deflate', STREAM_FILTER_WRITE, ['window' => 31]);
            $input = fopen($temporary, 'rb');
            if ($filter === false || $input === false || stream_copy_to_stream($input, $output) === false) {
                throw new RuntimeException;
            }
            fclose($input);
            $input = null;
            if (! fclose($output)) {
                throw new RuntimeException;
            }
            $output = null;

            $backups = array_values(array_filter(glob($directory.'/db-*.sqlite.gz') ?: [], fn (string $path): bool => preg_match('/^db-\d{4}-\d{2}-\d{2}-\d{6}\.sqlite\.gz$/D', basename($path)) === 1 && is_file($path) && ! is_link($path)));
            rsort($backups, SORT_STRING);
            foreach (array_slice($backups, $keep) as $old) {
                if (! unlink($old)) {
                    throw new RuntimeException;
                }
            }

            return ['filename' => basename($filename), 'size' => filesize($filename)];
        } catch (\Throwable) {
            if (is_resource($output)) {
                fclose($output);
            }
            if ($filename !== null && is_file($filename)) {
                unlink($filename);
            }
            throw new RuntimeException(__('privacy.backup.failed'));
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_string($temporary) && is_file($temporary)) {
                unlink($temporary);
            }
            umask($oldMask);
        }
    }

    private function resolvePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (! str_starts_with($path, '/') && ! preg_match('/^[a-z]:\//i', $path)) {
            $path = str_replace('\\', '/', getcwd()).'/'.$path;
        }
        if (preg_match('/^\/\/[^\/]+\/[^\/]+/', $path, $match)) {
            $root = $match[0].'/';
        } elseif (preg_match('/^[a-z]:\//i', $path)) {
            $root = substr($path, 0, 3);
        } else {
            $root = '/';
        }

        $resolvedPath = $root;
        foreach (explode('/', substr($path, strlen($root))) as $part) {
            if ($part === '..') {
                if ($resolvedPath !== $root) {
                    $resolvedPath = str_replace('\\', '/', dirname($resolvedPath));
                }
            } elseif ($part !== '.' && $part !== '') {
                $resolvedPath = rtrim($resolvedPath, '/').'/'.$part;
            }
            // Resolve existing symlinks before processing a following "..".
            $resolved = realpath($resolvedPath);
            if ($resolved !== false) {
                $resolvedPath = str_replace('\\', '/', $resolved);
            }
        }

        return $resolvedPath;
    }
}
