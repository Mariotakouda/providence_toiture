<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount([
                'products',
                'products as active_products_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'image' => $request->hasFile('image') ? $request->file('image')->store('categories', 'public') : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->audit->log('category.created', $category, null, $category->only(['name', 'description', 'is_active']));

        return redirect()->route('categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $isActive = $request->boolean('is_active');

        if (! $isActive && $category->is_active && $this->hasActiveProducts($category)) {
            return back()->withInput()->withErrors([
                'is_active' => 'Cette catégorie contient des produits actifs : archivez-les ou déplacez-les d’abord.',
            ]);
        }

        $category->fill([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $isActive,
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($category->image);
            $category->image = $request->file('image')->store('categories', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($category->image);
            $category->image = null;
        }

        $changes = $category->getDirty();

        if ($changes) {
            $old = array_intersect_key($category->getOriginal(), $changes);
            $category->save();
            $this->audit->log('category.updated', $category, $old, $changes);
        }

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        if ($category->is_active && $this->hasActiveProducts($category)) {
            return back()->with('error', 'Cette catégorie contient des produits actifs : archivez-les ou déplacez-les d’abord.');
        }

        $newStatus = ! $category->is_active;
        $category->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'category.activated' : 'category.deactivated',
            $category,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        return back()->with('success', $newStatus ? 'Catégorie activée.' : 'Catégorie désactivée.');
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function hasActiveProducts(Category $category): bool
    {
        return $category->products()->where('is_active', true)->exists();
    }
}
