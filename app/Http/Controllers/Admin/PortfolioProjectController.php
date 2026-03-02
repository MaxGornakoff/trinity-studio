<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioProjectRequest;
use App\Http\Requests\UpdatePortfolioProjectRequest;
use App\Models\PortfolioProject;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PortfolioProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/PortfolioProjects/Index', [
            'projects' => PortfolioProject::query()
                ->orderByDesc('is_featured')
                ->orderBy('order_column')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/PortfolioProjects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePortfolioProjectRequest $request)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);

        PortfolioProject::create($data);

        return redirect()
            ->route('admin.portfolio-projects.index')
            ->with('success', 'Проект добавлен.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PortfolioProject $portfolioProject)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PortfolioProject $portfolioProject)
    {
        return Inertia::render('Admin/PortfolioProjects/Edit', [
            'project' => $portfolioProject,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePortfolioProjectRequest $request, PortfolioProject $portfolioProject)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);

        $portfolioProject->update($data);

        return redirect()
            ->route('admin.portfolio-projects.index')
            ->with('success', 'Проект обновлён.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PortfolioProject $portfolioProject)
    {
        $portfolioProject->delete();

        return redirect()
            ->route('admin.portfolio-projects.index')
            ->with('success', 'Проект удалён.');
    }
}
