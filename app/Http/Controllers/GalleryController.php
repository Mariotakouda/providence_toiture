<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    /** Galerie publique des produits d'une gamme (catégorie active). */
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('gamme', [
            'category' => $category,
            'products' => $category->products()->where('is_active', true)->orderBy('name')->get(),
            'gammes' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
