<?php

namespace App\Services\Admin;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Models\Category;
use App\Models\Procedure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProcedureManager
{
    public function __construct(private SvgSanitizer $sanitizer) {}

    public function save(Procedure $procedure, array $data, ?UploadedFile $upload = null): bool
    {
        $steps = [];
        foreach (TimelinePhase::cases() as $sort => $phase) {
            $steps[] = [...$data['timeline_steps'][$phase->value], 'phase' => $phase->value, 'sort' => $sort];
        }
        $items = [];
        foreach (AdviceSection::cases() as $section) {
            foreach ($data['advice_items'][$section->value] ?? [] as $item) {
                if (blank($item['text']['et'] ?? null) && blank($item['text']['en'] ?? null)) {
                    continue;
                }
                $items[] = ['section' => $section->value, 'text' => $item['text'], 'merge_key' => $item['merge_key'] ?? null, 'sort' => count($items)];
            }
        }
        $attributes = collect($data)->only(['category_id', 'slug', 'name', 'summary', 'color', 'search_keywords', 'follow_up', 'review_note', 'sort', 'is_active'])->all();
        $attributes['color'] = filled($data['color'] ?? null) ? strtoupper($data['color']) : null;
        $attributes['category_id'] = (int) $data['category_id'];
        $before = $procedure->exists ? $this->content($procedure) : null;
        $oldStatus = $procedure->status ?? ProcedureStatus::Draft;
        $procedure->fill($attributes);
        $after = [
            'attributes' => $this->contentAttributes($procedure),
            'steps' => $this->canonical($steps),
            'items' => $this->canonical($items),
        ];
        $reset = $before !== null && $oldStatus !== ProcedureStatus::Draft && ($before !== $after || $upload !== null);
        $status = $reset ? ProcedureStatus::Draft : ProcedureStatus::from($data['status']);

        if (! $reset && $oldStatus === ProcedureStatus::Draft && $status === ProcedureStatus::Published) {
            throw ValidationException::withMessages(['status' => __('admin.procedure.review_first')]);
        }
        if (! $reset && $status !== ProcedureStatus::Draft && blank($data['reviewed_by'] ?? $procedure->reviewed_by)) {
            throw ValidationException::withMessages(['reviewed_by' => __('admin.procedure.reviewer_required')]);
        }
        if ($status === ProcedureStatus::Published) {
            foreach (['do', 'dont', 'red_flag'] as $section) {
                if (! collect($items)->contains('section', $section)) {
                    throw ValidationException::withMessages(['advice_items.'.$section => __('admin.procedure.publish_minimum')]);
                }
            }
        }
        $procedure->status = $status;
        $procedure->reviewed_by = $status === ProcedureStatus::Draft ? null : ($data['reviewed_by'] ?? $procedure->reviewed_by);
        $procedure->reviewed_at = $status === ProcedureStatus::Draft ? null : ($procedure->reviewed_at ?? now());

        $oldPath = $procedure->illustration_path;
        $source = filled($oldPath) ? $this->path($oldPath) : null;
        $original = $source && File::isFile($source) ? File::get($source) : null;
        $destination = null;
        $bytes = null;
        if ($upload || $original !== null) {
            $extension = $upload ? strtolower($upload->getClientOriginalExtension()) : pathinfo($oldPath, PATHINFO_EXTENSION);
            $relative = 'illustrations/'.Category::findOrFail($data['category_id'])->slug.'/'.$data['slug'].'.'.$extension;
            $target = $this->path($relative);
            if ($target !== $source && File::exists($target)) {
                throw ValidationException::withMessages(['illustration' => __('admin.procedure.file_conflict')]);
            }
            if ($upload || $target !== $source) {
                $destination = $target;
                $content = $upload ? $upload->getContent() : $original;
                $bytes = $extension === 'svg' ? $this->sanitizer->sanitize($content, 200 * 1024, 'illustration') : $content;
                $procedure->illustration_path = $relative;
            }
        } elseif (! $procedure->exists) {
            // The existing schema uses a non-null path; empty means no illustration.
            $procedure->illustration_path = '';
        }

        try {
            DB::transaction(function () use ($procedure, $steps, $items, $destination, $bytes, $source, $oldPath): void {
                $procedure->save();
                $procedure->timelineSteps()->delete();
                $procedure->timelineSteps()->createMany($steps);
                $procedure->adviceItems()->delete();
                $procedure->adviceItems()->createMany($items);
                if ($destination !== null) {
                    File::ensureDirectoryExists(dirname($destination));
                    File::replace($destination, $bytes);
                    if ($source !== null && $source !== $destination && ! Procedure::whereKeyNot($procedure->id)->where('illustration_path', $oldPath)->exists() && File::exists($source) && ! File::delete($source)) {
                        throw ValidationException::withMessages(['illustration' => __('admin.procedure.file_error')]);
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($destination !== null) {
                if ($original !== null && $source !== null) {
                    File::replace($source, $original);
                }
                if (($source !== $destination || $original === null) && File::exists($destination)) {
                    File::delete($destination);
                }
            }
            throw $exception;
        }

        return $reset;
    }

    public function delete(Procedure $procedure): void
    {
        $path = filled($procedure->illustration_path) ? $this->path($procedure->illustration_path) : null;
        DB::transaction(function () use ($procedure, $path): void {
            $shared = Procedure::whereKeyNot($procedure->id)->where('illustration_path', $procedure->illustration_path)->exists();
            $procedure->delete();
            if (! $shared && $path && File::exists($path) && ! File::delete($path)) {
                throw ValidationException::withMessages(['illustration' => __('admin.procedure.file_error')]);
            }
        });
    }

    private function content(Procedure $procedure): array
    {
        $items = [];
        foreach (AdviceSection::cases() as $section) {
            foreach ($procedure->adviceItems()->where('section', $section->value)->get() as $item) {
                $items[] = ['section' => $item->section->value, 'text' => $item->getTranslations('text'), 'merge_key' => $item->merge_key, 'sort' => count($items)];
            }
        }

        return [
            'attributes' => $this->contentAttributes($procedure),
            'steps' => $this->canonical($procedure->timelineSteps()->get()->map(fn ($step, $sort) => [
                'phase' => $step->phase->value, 'marker' => $step->getTranslations('marker'), 'title' => $step->getTranslations('title'), 'body' => $step->getTranslations('body'), 'sort' => $sort,
            ])->all()),
            'items' => $this->canonical($items),
        ];
    }

    private function contentAttributes(Procedure $procedure): array
    {
        $attributes = $procedure->only(['category_id', 'slug', 'color']);
        foreach (['name', 'summary', 'search_keywords', 'follow_up', 'review_note'] as $field) {
            $attributes[$field] = array_replace(['et' => null, 'en' => null], $procedure->getTranslations($field));
        }

        return $this->canonical($attributes);
    }

    private function canonical(array $values): array
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->canonical($value);
            } elseif ($value === '') {
                $value = null;
            }
        }
        ksort($values);

        return $values;
    }

    private function path(string $relative): string
    {
        if (! preg_match('~^illustrations/(?:[a-z0-9]+(?:-[a-z0-9]+)*/)*[a-z0-9]+(?:-[a-z0-9]+)*\.(svg|png)$~D', $relative)) {
            throw ValidationException::withMessages(['illustration' => __('admin.procedure.file_error')]);
        }
        $candidate = public_path();
        foreach (explode('/', $relative) as $part) {
            $candidate .= '/'.$part;
            if (is_link($candidate)) {
                throw ValidationException::withMessages(['illustration' => __('admin.procedure.file_error')]);
            }
        }

        return public_path($relative);
    }
}
