<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioProjectRequest;
use App\Http\Requests\UpdatePortfolioProjectRequest;
use App\Models\PortfolioProject;
use Illuminate\Support\Facades\Storage;
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
            // Транслитерация кириллицы в латиницу перед созданием slug
            $transliterated = Str::transliterate($data['title']);
            $data['slug'] = Str::slug($transliterated);
        }

        // Обработка загрузки изображения для десктопного мокапа
        if ($request->hasFile('desktop_mockup_image')) {
            $file = $request->file('desktop_mockup_image');
            $filename = $data['slug'] . '-desktop.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('mockups', $filename, 'public');
            $data['desktop_mockup_image'] = '/storage/' . $path;
        }

        // Обработка загрузки изображения для мобильного мокапа
        if ($request->hasFile('mobile_mockup_image')) {
            $file = $request->file('mobile_mockup_image');
            $filename = $data['slug'] . '-mobile.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('mockups', $filename, 'public');
            $data['mobile_mockup_image'] = '/storage/' . $path;
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['show_in_slider'] = (bool) ($data['show_in_slider'] ?? false);

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
            // Транслитерация кириллицы в латиницу перед созданием slug
            $transliterated = Str::transliterate($data['title']);
            $data['slug'] = Str::slug($transliterated);
        }

        // Обработка загрузки изображения для десктопного мокапа
        if ($request->hasFile('desktop_mockup_image')) {
            // Удаляем старое изображение
            if ($portfolioProject->desktop_mockup_image) {
                $oldPath = str_replace('/storage/', '', $portfolioProject->desktop_mockup_image);
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('desktop_mockup_image');
            $filename = $data['slug'] . '-desktop.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('mockups', $filename, 'public');
            $data['desktop_mockup_image'] = '/storage/' . $path;
        }

        // Обработка загрузки изображения для мобильного мокапа
        if ($request->hasFile('mobile_mockup_image')) {
            // Удаляем старое изображение
            if ($portfolioProject->mobile_mockup_image) {
                $oldPath = str_replace('/storage/', '', $portfolioProject->mobile_mockup_image);
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('mobile_mockup_image');
            $filename = $data['slug'] . '-mobile.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('mockups', $filename, 'public');
            $data['mobile_mockup_image'] = '/storage/' . $path;
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['show_in_slider'] = (bool) ($data['show_in_slider'] ?? false);

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
        // Удаляем изображения мокапов
        if ($portfolioProject->desktop_mockup_image) {
            $path = str_replace('/storage/', '', $portfolioProject->desktop_mockup_image);
            Storage::disk('public')->delete($path);
        }
        
        if ($portfolioProject->mobile_mockup_image) {
            $path = str_replace('/storage/', '', $portfolioProject->mobile_mockup_image);
            Storage::disk('public')->delete($path);
        }
        
        $portfolioProject->delete();

        return redirect()
            ->route('admin.portfolio-projects.index')
            ->with('success', 'Проект удалён.');
    }
}
