<?php

namespace App\Http\Controllers;

use App\Enums\ConsentPurpose;
use App\Http\Requests\LeafletRequest;
use App\Http\Requests\StoreWizardProceduresRequest;
use App\Models\Category;
use App\Models\Consent;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\LeafletDocument;
use App\Services\Leaflet\PdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WizardController extends Controller
{
    public function __construct(private readonly LeafletBuilder $builder) {}

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

    public function details(Request $request): View|RedirectResponse
    {
        $doc = $this->document($request);

        return $doc->sections ? view('wizard.details', compact('doc')) : redirect()->route('wizard.procedures');
    }

    public function storeDetails(LeafletRequest $request): RedirectResponse
    {
        if (! $this->document($request)->sections) {
            return redirect()->route('wizard.procedures');
        }

        $firstName = $request->validated('first_name');
        $date = $request->validated('date');
        $personal = $firstName !== null || $date !== null;

        if ($personal) {
            Consent::create([
                'purpose' => ConsentPurpose::PersonalisedLeaflet,
                'user_id' => $request->user()?->id,
                'text_version' => config('leaflet.consent_version'),
                'locale' => app()->getLocale(),
                'given_at' => now(),
            ]);
        }

        $request->session()->put(['wizard.first_name' => $firstName, 'wizard.date' => $date, 'wizard.consent' => $personal]);

        return redirect()->route('wizard.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $doc = $this->document($request);

        return $doc->sections ? view('wizard.preview', compact('doc')) : redirect()->route('wizard.procedures');
    }

    public function pdf(Request $request, PdfRenderer $renderer): Response|RedirectResponse
    {
        $doc = $this->document($request);

        if (! $doc->sections) {
            return redirect()->route('wizard.procedures');
        }

        $bytes = $renderer->render($doc);
        $request->session()->forget('wizard');

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.__('wizard.pdf_filename').'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function document(Request $request): LeafletDocument
    {
        return $this->builder->build(
            $request->session()->get('wizard.procedures', []),
            app()->getLocale(),
            $request->session()->get('wizard.first_name'),
            $request->session()->get('wizard.date'),
        );
    }
}
