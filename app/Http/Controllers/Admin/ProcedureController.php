<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcedureRequest;
use App\Models\AdviceItem;
use App\Models\Category;
use App\Models\Procedure;
use App\Services\Admin\ProcedureManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProcedureController extends Controller
{
    public function __construct(private ProcedureManager $procedures) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::enum(ProcedureStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $procedures = Procedure::with('category')
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereRaw("json_extract(name, ?) like ? escape '\\'", ['$.'.app()->getLocale(), '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%']))
            ->orderBy(Category::select('sort')->whereColumn('categories.id', 'procedures.category_id'))
            ->orderBy('category_id')->orderBy('sort')->orderBy('id')->paginate(30)->withQueryString();

        return view('admin.procedures.index', [
            'procedures' => $procedures, 'categories' => Category::orderBy('sort')->get(), 'statuses' => ProcedureStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Procedure(['sort' => 0, 'is_active' => true, 'status' => ProcedureStatus::Draft]));
    }

    public function edit(Procedure $procedure): View
    {
        return $this->form($procedure->load(['timelineSteps', 'adviceItems', 'category']));
    }

    private function form(Procedure $procedure): View
    {
        return view('admin.procedures.form', [
            'procedure' => $procedure,
            'categories' => Category::orderBy('sort')->orderBy('id')->get(),
            'statuses' => ProcedureStatus::cases(), 'phases' => TimelinePhase::cases(), 'sections' => AdviceSection::cases(),
            'mergeKeys' => AdviceItem::whereNotNull('merge_key')->distinct()->orderBy('merge_key')->pluck('merge_key'),
        ]);
    }

    public function store(ProcedureRequest $request): RedirectResponse
    {
        $this->procedures->save(new Procedure, $request->validated(), $request->file('illustration'));

        return to_route('admin.procedures.index')->with('success', __('admin.procedure.created'));
    }

    public function update(ProcedureRequest $request, Procedure $procedure): RedirectResponse
    {
        $reset = $this->procedures->save($procedure, $request->validated(), $request->file('illustration'));

        return to_route('admin.procedures.edit', $procedure)->with($reset ? 'warning' : 'success', __($reset ? 'admin.procedure.reset' : 'admin.procedure.updated'));
    }

    public function destroy(Procedure $procedure): RedirectResponse
    {
        $this->procedures->delete($procedure);

        return to_route('admin.procedures.index')->with('success', __('admin.procedure.deleted'));
    }
}
