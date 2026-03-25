<?php

use App\Http\Controllers\Admin\MapPopupLocationController;
use App\Http\Controllers\Admin\PortfolioProjectController;
use App\Http\Controllers\PortfolioController;
use App\Models\MapPopupLocation;
use App\Models\PortfolioProject;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    try {
        $featuredProjects = PortfolioProject::query()
            ->where('is_published', true)
            ->orderByDesc('is_featured')
            ->orderBy('order_column')
            ->limit(6)
            ->get();
        
        $sliderProjects = PortfolioProject::query()
            ->where('is_published', true)
            ->where('show_in_slider', true)
            ->orderBy('order_column')
            ->get();

        $mapProjects = PortfolioProject::query()
            ->where('is_published', true)
            ->whereNotNull('map_land_id')
            ->whereNotNull('logo_image')
            ->select(['id', 'title', 'slug', 'map_land_id', 'logo_image', 'properties', 'region'])
            ->orderBy('order_column')
            ->get();

        $mapPopupLocations = MapPopupLocation::query()
            ->orderBy('sort_order')
            ->get(['land_id', 'title', 'hover_image'])
            ->mapWithKeys(fn (MapPopupLocation $location) => [
                $location->land_id => [
                    'title' => $location->title,
                    'hover_image' => $location->hover_image,
                ],
            ])
            ->all();
    } catch (\Throwable $exception) {
        $featuredProjects = [];
        $sliderProjects = [];
        $mapProjects = [];
        $mapPopupLocations = [];
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'featuredProjects' => $featuredProjects,
        'sliderProjects' => $sliderProjects,
        'mapProjects' => $mapProjects,
        'mapPopupLocations' => $mapPopupLocations,
    ]);
})->name('home');

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{slug}', [PortfolioController::class, 'show'])->name('portfolio.show');

Route::get('/dashboard', function () {
    try {
        $stats = [
            'total' => PortfolioProject::count(),
            'published' => PortfolioProject::where('is_published', true)->count(),
        ];
    } catch (\Throwable $exception) {
        $stats = [
            'total' => 0,
            'published' => 0,
        ];
    }

    return Inertia::render('Dashboard', [
        'stats' => $stats,
    ]);
})->middleware(['auth', 'admin.email'])->name('dashboard');

Route::middleware(['auth', 'admin.email'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('portfolio-projects', PortfolioProjectController::class)->except(['show']);
    Route::get('map-popup-locations', [MapPopupLocationController::class, 'edit'])->name('map-popup-locations.edit');
    Route::put('map-popup-locations', [MapPopupLocationController::class, 'update'])->name('map-popup-locations.update');
});

require __DIR__.'/auth.php';
