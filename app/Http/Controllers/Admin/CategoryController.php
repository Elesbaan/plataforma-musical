<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => $this->categories->list(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->save($request->validated());

        return back()->with('success', "Se creó la categoría {$category->name}.");
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->save($request->validated(), $category);

        return back()->with('success', "Se actualizó la categoría {$category->name}.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        return back()->with('success', "Se eliminó la categoría {$category->name}.");
    }
}
