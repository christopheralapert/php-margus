<?php

namespace App\Http\Controllers;

use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\OnlineLeafletProcedures;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OnlineLeafletController extends Controller
{
    public function __invoke(string $combo, OnlineLeafletProcedures $procedures, LeafletBuilder $builder): View
    {
        $selected = $procedures->resolve($combo);
        $doc = $builder->build($selected->pluck('id')->all(), app()->getLocale(), null, null);
        $title = $selected->map(fn ($procedure): string => $procedure->getTranslation('name', $doc->locale))->implode(', ');

        return view('online.show', compact('doc', 'title'));
    }

    public function personalize(Request $request, string $combo, OnlineLeafletProcedures $procedures): RedirectResponse
    {
        $selected = $procedures->resolve($combo);
        $request->session()->forget('wizard');
        $request->session()->put('wizard.procedures', $selected->pluck('id')->unique()->values()->all());

        return redirect()->route('wizard.procedures');
    }
}
