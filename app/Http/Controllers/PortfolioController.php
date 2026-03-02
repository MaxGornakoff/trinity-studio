<?php

namespace App\Http\Controllers;

use App\Models\PortfolioProject;
use Inertia\Inertia;

class PortfolioController extends Controller
{
    public function index()
    {
        try {
            $projects = PortfolioProject::query()
                ->where('is_published', true)
                ->orderByDesc('is_featured')
                ->orderBy('order_column')
                ->latest('published_at')
                ->get();
        } catch (\Throwable $exception) {
            $projects = [];
        }

        return Inertia::render('Portfolio/Index', [
            'projects' => $projects,
        ]);
    }

    public function show(string $slug)
    {
        try {
            $project = PortfolioProject::query()
                ->where('is_published', true)
                ->where('slug', $slug)
                ->firstOrFail();
        } catch (\Throwable $exception) {
            abort(404);
        }

        return Inertia::render('Portfolio/Show', [
            'project' => $project,
        ]);
    }
}
