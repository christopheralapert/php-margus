<?php

namespace App\Console\Commands;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class RenderSampleLeaflets extends Command
{
    protected $signature = 'leaflet:render-samples {--only=} {--print}';

    public function getDescription(): string
    {
        return __('pdf.samples_description');
    }

    public function handle(): int
    {
        $connection = config('database.default');
        $previousSettings = app(Settings::class);
        $previousNow = Carbon::getTestNow();
        $previousImmutableNow = CarbonImmutable::getTestNow();
        $previousSampleConnection = config('database.connections.leaflet_samples');

        try {
            // A separate connection never opens, migrates or seeds the local DB.
            config(['database.connections.leaflet_samples' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            DB::purge('leaflet_samples');
            DB::setDefaultConnection('leaflet_samples');
            // Settings must not read or invalidate a persistent cache either.
            app()->instance(Settings::class, new Settings(Cache::store('array')));
            app(Settings::class)->flush();
            Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00'));
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-24 10:00:00'));
            $this->callSilent('migrate', ['--database' => 'leaflet_samples', '--force' => true]);
            $this->callSilent('db:seed', ['--database' => 'leaflet_samples', '--class' => DatabaseSeeder::class, '--force' => true]);

            // Count all patient-facing content, not search keywords or review notes.
            $longest = Procedure::with(['timelineSteps', 'adviceItems'])->get()
                ->sortByDesc(function (Procedure $procedure): int {
                    $text = $procedure->getTranslation('name', 'et').$procedure->getTranslation('summary', 'et').$procedure->getTranslation('follow_up', 'et');
                    foreach ($procedure->timelineSteps as $step) {
                        $text .= $step->getTranslation('marker', 'et').$step->getTranslation('title', 'et').$step->getTranslation('body', 'et');
                    }
                    foreach ($procedure->adviceItems as $item) {
                        $text .= $item->getTranslation('text', 'et');
                    }

                    return mb_strlen($text);
                })->firstOrFail()->slug;

            $samples = [
                'a-dental-et' => [['tooth-extraction'], 'et', 'Mari', '2026-11-24', false],
                'b-dental-en' => [['tooth-extraction'], 'en', 'Mary', '2026-11-24', false],
                'c-multi-et' => [['tooth-extraction', 'mole-removal', 'knee-arthroscopy'], 'et', null, null, false],
                'd-long-et' => [[$longest], 'et', null, null, false],
                'e-anon-et' => [['cataract-surgery'], 'et', null, null, false],
                'f-print-et' => [['tooth-extraction'], 'et', 'Mari', '2026-11-24', true],
                'g-ent-et' => [['tonsillectomy'], 'et', null, null, false],
            ];
            $only = $this->option('only');
            if ($only !== null && $only !== 'icons' && ! array_key_exists($only, $samples)) {
                $this->error(__('pdf.samples_unknown', ['name' => $only]));

                return self::INVALID;
            }

            File::ensureDirectoryExists(base_path('tests/visual/output'));
            if ($only === null || $only === 'icons') {
                File::put(base_path('tests/visual/output/icons.pdf'), app(PdfRenderer::class)->renderIconSheet());
                $this->line(__('pdf.samples_written', ['name' => 'icons', 'procedure' => __('pdf.icons_title')]));
            }
            foreach ($samples as $name => [$slugs, $locale, $firstName, $date, $print]) {
                if ($only !== null && $only !== $name) {
                    continue;
                }
                $ids = array_map(fn (string $slug): int => Procedure::where('slug', $slug)->firstOrFail()->id, $slugs);
                $doc = app(LeafletBuilder::class)->build($ids, $locale, $firstName, $date);
                $pdf = app(PdfRenderer::class)->render($doc, $print || $this->option('print'));
                File::put(base_path('tests/visual/output/'.$name.'.pdf'), $pdf);
                $this->line(__('pdf.samples_written', ['name' => $name, 'procedure' => implode(', ', $slugs)]));
            }

            return self::SUCCESS;
        } finally {
            DB::purge('leaflet_samples');
            DB::setDefaultConnection($connection);
            config(['database.connections.leaflet_samples' => $previousSampleConnection]);
            app(Settings::class)->flush();
            app()->instance(Settings::class, $previousSettings);
            Carbon::setTestNow($previousNow);
            CarbonImmutable::setTestNow($previousImmutableNow);
        }
    }
}
