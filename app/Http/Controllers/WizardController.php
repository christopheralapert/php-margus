<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWizardProceduresRequest;
use App\Models\Category;
use App\Models\Procedure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WizardController extends Controller
{
    public function home(): View
    {
        return view('wizard.home');
    }

    public function procedures(): View
    {
        $categories = Category::query()
            ->whereHas('procedures', fn ($query) => $query->active())
            ->with(['procedures' => fn ($query) => $query->active()->orderBy('id')])
            ->orderBy('sort')->orderBy('id')->get();

        return view('wizard.procedures', compact('categories'));
    }

    public function store(StoreWizardProceduresRequest $request): RedirectResponse
    {
        $request->session()->put('wizard.procedures', array_values(array_map('intval', $request->validated('procedures'))));

        return redirect()->route('wizard.details');
    }

    public function details(Request $request): View
    {
        $ids = $request->session()->get('wizard.procedures', []);
        $procedures = Procedure::active()->whereIn('id', $ids)->get()
            ->sortBy(fn (Procedure $procedure) => array_search($procedure->id, $ids, true));

        return view('wizard.details', compact('procedures'));
    }
}
