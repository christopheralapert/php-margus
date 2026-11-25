<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Procedure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $reviewNote = (new Procedure)->getTranslations('review_note');

            foreach (glob(__DIR__.'/content/*.php') as $file) {
                $slug = basename($file, '.php');

                if (str_starts_with($slug, '_')) {
                    continue;
                }

                $category = Category::where('slug', $slug)->first()
                    ?? throw new RuntimeException("Missing category [{$slug}]. Run CategorySeeder before ProcedureSeeder.");

                foreach (require $file as $sort => $definition) {
                    $steps = $definition['timeline_steps'];
                    $advice = $definition['advice_items'];
                    unset($definition['timeline_steps'], $definition['advice_items']);

                    $procedure = Procedure::updateOrCreate(['slug' => $definition['slug']], [
                        ...$definition,
                        'category_id' => $category->id,
                        'illustration_path' => 'illustrations/'.$slug.'/'.$definition['slug'].'.svg',
                        'status' => 'draft',
                        'review_note' => $reviewNote,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'is_active' => true,
                        'sort' => $sort,
                    ]);

                    $procedure->timelineSteps()->delete();
                    $procedure->adviceItems()->delete();
                    $procedure->timelineSteps()->createMany($steps);
                    $procedure->adviceItems()->createMany($advice);
                }
            }
        });
    }
}
