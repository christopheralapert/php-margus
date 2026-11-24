<?php

namespace App\Services\Admin;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CategoryManager
{
    public function __construct(private SvgSanitizer $sanitizer) {}

    public function save(Category $category, array $data, ?UploadedFile $upload): void
    {
        $destination = $this->path('icons/categories/'.$data['slug'].'.svg');
        $source = $category->exists ? $this->path($category->icon_path) : null;

        if ($destination !== $source && File::exists($destination)) {
            throw ValidationException::withMessages(['slug' => __('admin.icon_conflict')]);
        }

        $original = $source && File::isFile($source) ? File::get($source) : null;
        $svg = $upload ? $this->sanitizer->sanitize($upload->getContent()) : null;

        if (! $upload && $source !== $destination) {
            $svg = $this->sanitizer->sanitize($original ?? '');
        }

        try {
            DB::transaction(function () use ($category, $data, $svg, $destination, $source): void {
                $category->fill(collect($data)->only(['name', 'slug', 'color', 'sort', 'is_active'])->all());
                $category->color = strtoupper($data['color']);
                $category->icon_path = 'icons/categories/'.$data['slug'].'.svg';
                $category->save();

                if ($svg !== null) {
                    File::ensureDirectoryExists(dirname($destination));
                    File::replace($destination, $svg);
                }

                if ($source && $source !== $destination && File::exists($source) && ! File::delete($source)) {
                    throw ValidationException::withMessages(['icon' => __('admin.file_error')]);
                }
            });
        } catch (Throwable $exception) {
            if ($original !== null) {
                File::replace($source, $original);
            }
            if (($destination !== $source || $original === null) && File::exists($destination)) {
                File::delete($destination);
            }

            throw $exception;
        }
    }

    public function delete(Category $category): bool
    {
        return DB::transaction(function () use ($category): bool {
            if ($category->procedures()->exists()) {
                return false;
            }

            $path = $this->path($category->icon_path);
            $category->delete();

            if (File::exists($path) && ! File::delete($path)) {
                throw ValidationException::withMessages(['icon' => __('admin.file_error')]);
            }

            return true;
        });
    }

    public function icon(Category $category): string
    {
        try {
            $path = $this->path($category->icon_path);

            return File::isFile($path) ? $this->sanitizer->sanitize(File::get($path)) : '';
        } catch (ValidationException) {
            return '';
        }
    }

    private function path(string $relative): string
    {
        if (! preg_match('~^icons/categories/[a-z0-9]+(?:-[a-z0-9]+)*\.svg$~D', $relative)) {
            throw ValidationException::withMessages(['icon' => __('admin.file_error')]);
        }

        $path = public_path($relative);
        // Never follow a directory or file symlink into another part of the filesystem.
        foreach ([public_path('icons'), public_path('icons/categories'), $path] as $candidate) {
            if (is_link($candidate)) {
                throw ValidationException::withMessages(['icon' => __('admin.file_error')]);
            }
        }

        return $path;
    }
}
