<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSavedLeafletRequest;
use App\Http\Requests\LeafletFormatRequest;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Services\Leaflet\SaveAccountLeaflet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountLeafletController extends Controller
{
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

    public function pdf(SavedLeaflet $leaflet, LeafletBuilder $builder, PdfRenderer $renderer): StreamedResponse|RedirectResponse
    {
        Gate::authorize('download', $leaflet);
        $doc = $builder->build($leaflet->procedure_ids, $leaflet->locale, $leaflet->first_name, $leaflet->procedure_date);

        if (! $doc->sections) {
            return redirect()->route('account.leaflets')->with('status', __('account.unavailable'));
        }

        $bytes = $renderer->render($doc, format: $leaflet->format);
        $leaflet->update(['last_downloaded_at' => now()]);

        return response()->streamDownload(function () use ($bytes): void {
            echo $bytes;
        }, $leaflet->format->filename($leaflet->locale), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function destroy(DeleteSavedLeafletRequest $request, SavedLeaflet $leaflet): RedirectResponse
    {
        $leaflet->delete();

        return redirect()->route('account.leaflets')->with('status', __('account.deleted'));
    }
}
