<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapPopupLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MapPopupLocationController extends Controller
{
    public function edit()
    {
        $rows = MapPopupLocation::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'land_id', 'title', 'hover_image'])
            ->map(fn (MapPopupLocation $location) => [
                'id' => $location->id,
                'land_id' => $location->land_id,
                'title' => $location->title,
                'hover_image' => $location->hover_image,
            ])
            ->values()
            ->all();

        if (empty($rows)) {
            $rows = [
                [
                    'id' => null,
                    'land_id' => '',
                    'title' => '',
                    'hover_image' => null,
                ],
            ];
        }

        return Inertia::render('Admin/MapPopupLocations/Edit', [
            'rows' => $rows,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.land_id' => ['required', 'string', 'max:100', 'regex:/^land_\d+$/', 'distinct'],
            'rows.*.title' => ['required', 'string', 'max:255'],
            'rows.*.existing_hover_image' => ['nullable', 'string', 'max:255'],
            'rows.*.hover_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:5120'],
        ], [
            'rows.*.land_id.regex' => 'ID должен быть в формате land_44.',
            'rows.*.land_id.distinct' => 'ID path не должен повторяться.',
            'rows.*.hover_image.mimes' => 'Фон должен быть файлом формата jpeg, png, jpg, gif, webp или svg.',
        ]);

        $existingImages = MapPopupLocation::query()
            ->pluck('hover_image')
            ->filter()
            ->values();

        $timestamp = now();
        $rows = collect($validated['rows'])
            ->map(function (array $row, int $index) use ($request, $timestamp) {
                $hoverImage = isset($row['existing_hover_image']) && trim((string) $row['existing_hover_image']) !== ''
                    ? trim((string) $row['existing_hover_image'])
                    : null;

                $file = data_get($request->file('rows', []), "$index.hover_image");
                if ($file) {
                    $filename = trim((string) $row['land_id']) . '-flag-' . time() . '-' . $index . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('map-popup-flags', $filename, 'public');
                    $hoverImage = '/storage/' . $path;
                }

                return [
                'land_id' => trim($row['land_id']),
                'title' => trim($row['title']),
                'hover_image' => $hoverImage,
                'sort_order' => $index,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            })
            ->values();

        $newImages = $rows
            ->pluck('hover_image')
            ->filter()
            ->values();

        $imagesToDelete = $existingImages
            ->diff($newImages)
            ->unique()
            ->values();

        DB::transaction(function () use ($rows) {
            MapPopupLocation::query()->delete();
            MapPopupLocation::query()->insert($rows->all());
        });

        foreach ($imagesToDelete as $imagePath) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $imagePath));
        }

        return redirect()
            ->route('admin.map-popup-locations.edit')
            ->with('success', 'Соответствия карты обновлены.');
    }
}