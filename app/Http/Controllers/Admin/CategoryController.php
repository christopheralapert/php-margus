<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProcedureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Procedure;
use App\Services\Admin\CategoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private CategoryManager $categories) {}

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'categoryCount' => Category::count(),
            'statuses' => ProcedureStatus::cases(),
            'procedureCounts' => Procedure::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function index(): View
    {
        $categories = Category::withCount('procedures')->orderBy('sort')->orderBy('id')->get();

        return view('admin.categories.index', [
            'categories' => $categories,
            'icons' => $categories->mapWithKeys(fn (Category $category) => [$category->id => $this->categories->icon($category)]),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', ['category' => new Category(['color' => '#2E6FD8', 'sort' => 0, 'is_active' => true])]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->save(new Category, $request->validated(), $request->file('icon'));

        return to_route('admin.categories.index')->with('success', __('admin.created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', ['category' => $category, 'icon' => $this->categories->icon($category)]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->save($category, $request->validated(), $request->file('icon'));

        return to_route('admin.categories.index')->with('success', __('admin.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $deleted = $this->categories->delete($category);

        return to_route('admin.categories.index')->with($deleted ? 'success' : 'error', __($deleted ? 'admin.deleted' : 'admin.has_procedures'));
    }
}
