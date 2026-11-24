<?php

namespace App\Services\Admin;

use App\Services\Leaflet\ThemeColor;
use App\Support\Settings;
use ErrorException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ClinicSettings
{
    private const KEYS = [
        'clinic.name', 'clinic.phone', 'clinic.after_hours_phone', 'clinic.emergency',
        'clinic.email', 'clinic.address', 'clinic.hours', 'clinic.website',
        'brand.colors', 'leaflet.disclaimer', 'retention.saved_leaflet_months', 'retention.unverified_account_days',
    ];

    public function __construct(private readonly Settings $settings, private readonly SvgSanitizer $sanitizer) {}

    public function values(): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            data_set($values, $key, $this->settings->get($key));
        }

        return $values;
    }

    public function warnings(array $colors): array
    {
        $warnings = [];
        foreach ([['ink', 'paper', 7], ['primary', null, 4.5], ['danger', null, 4.5]] as [$foreground, $background, $minimum]) {
            $ratio = ThemeColor::contrast($colors[$foreground], $background ? $colors[$background] : '#FFFFFF');
            if ($ratio < $minimum) {
                $warnings[] = __('settings.contrast.'.$foreground, ['ratio' => number_format($ratio, 2), 'minimum' => $minimum]);
            }
        }

        return $warnings;
    }

    public function save(array $values, ?UploadedFile $logo = null): void
    {
        $svg = $logo ? $this->sanitizer->sanitize($logo->getContent(), 200 * 1024, 'logo') : null;
        $original = null;
        $backup = null;
        $replaced = false;
        $path = public_path('brand/logo.svg');

        try {
            DB::transaction(function () use ($values, $svg, $path, &$original, &$backup, &$replaced): void {
                foreach (self::KEYS as $key) {
                    $value = data_get($values, $key);
                    if ($key === 'brand.colors') {
                        $value = array_map(strtoupper(...), $value);
                    } elseif (str_starts_with($key, 'retention.')) {
                        $value = (int) $value;
                    }
                    $this->settings->set($key, $value);
                }

                if ($svg !== null) {
                    foreach ([public_path('brand'), public_path('brand/backup'), $path] as $candidate) {
                        if (is_link($candidate)) {
                            throw ValidationException::withMessages(['logo' => __('settings.logo_error')]);
                        }
                    }
                    File::ensureDirectoryExists(public_path('brand/backup'));
                    if (File::exists($path)) {
                        $original = File::get($path);
                        $stamp = now();
                        do {
                            $backup = public_path('brand/backup/logo-'.$stamp->format('Ymd-His').'.svg');
                            $stamp->addSecond();
                        } while (File::exists($backup) || is_link($backup));
                        if (! File::copy($path, $backup)) {
                            throw ValidationException::withMessages(['logo' => __('settings.logo_error')]);
                        }
                    }
                    File::replace($path, $svg);
                    $replaced = true;
                    $this->settings->set('brand.logo_path', 'brand/logo.svg');
                }
            });
        } catch (Throwable $exception) {
            if ($replaced) {
                $original === null ? File::delete($path) : File::replace($path, $original);
            }
            if ($backup !== null) {
                File::delete($backup);
            }
            if ($exception instanceof ErrorException) {
                throw ValidationException::withMessages(['logo' => __('settings.logo_error')]);
            }
            throw $exception;
        } finally {
            $this->settings->flush();
        }

        if ($svg !== null) {
            $backups = collect(File::glob(public_path('brand/backup/logo-????????-??????.svg')))->sortDesc()->values();
            foreach ($backups->skip(5) as $old) {
                File::delete($old);
            }
        }
    }
}
