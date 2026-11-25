<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSavedLeafletRequest;
use App\Http\Requests\LeafletFormatRequest;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfFailureResponse;
use App\Services\Leaflet\RendersPdf;
use App\Services\Leaflet\SaveAccountLeaflet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountLeafletController extends Controller
{
    public function __construct(private readonly PdfFailureResponse $pdfFailure) {}

    public function index(Request $request): View
    {
        $leaflets = $request->user()->savedLeaflets()->latest()->orderByDesc('id')->paginate(10);
        $procedures = Procedure::active()->whereIn('id', $leaflets->getCollection()->pluck('procedure_ids')->flatten()->unique())->get()->keyBy('id');

        return view('account.leaflets', compact('leaflets', 'procedures'));
    }

    public function store(LeafletFormatRequest $request, SaveAccountLeaflet $saver): RedirectResponse
    {
        if (! $saver->save($request->user(), $request->session()->get('wizard', []), app()->getLocale(), $request->selectedFormat())) {
            return redirect()->route('wizard.procedures');
        }

        return redirect()->route('account.leaflets')->with('status', __('account.saved'));
    }

    public function pdf(LeafletFormatRequest $request, SavedLeaflet $leaflet, LeafletBuilder $builder, RendersPdf $renderer): StreamedResponse|RedirectResponse|Response
    {
        Gate::authorize('download', $leaflet);
        $doc = $builder->build($leaflet->procedure_ids, $leaflet->locale, $leaflet->first_name, $leaflet->procedure_date);

        if (! $doc->sections) {
            return redirect()->route('account.leaflets')->with('status', __('account.unavailable'));
        }

        $format = $request->has('format') ? $request->selectedFormat() : $leaflet->format;
        $response = $this->pdfFailure->download($renderer, $doc, $format, $format->filename($leaflet->locale), $request->query('print') === '1', stream: true);
        if ($response->isSuccessful()) {
            $leaflet->update(['last_downloaded_at' => now()]);
        }

        return $response;
    }

    public function destroy(DeleteSavedLeafletRequest $request, SavedLeaflet $leaflet): RedirectResponse
    {
        $leaflet->delete();

        return redirect()->route('account.leaflets')->with('status', __('account.deleted'));
    }
}
