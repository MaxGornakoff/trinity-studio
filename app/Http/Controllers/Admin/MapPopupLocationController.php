<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapPopupLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MapPopupLocationController extends Controller
{
    public function edit()
    {
        $rows = MapPopupLocation::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'land_id', 'title'])
            ->map(fn (MapPopupLocation $location) => [
                'id' => $location->id,
                'land_id' => $location->land_id,
                'title' => $location->title,
            ])
            ->values()
            ->all();

        if (empty($rows)) {
            $rows = [
                [
                    'id' => null,
                    'land_id' => '',
                    'title' => '',
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
        ], [
            'rows.*.land_id.regex' => 'ID должен быть в формате land_44.',
            'rows.*.land_id.distinct' => 'ID path не должен повторяться.',
        ]);

        $timestamp = now();
        $rows = collect($validated['rows'])
            ->map(fn (array $row, int $index) => [
                'land_id' => trim($row['land_id']),
                'title' => trim($row['title']),
                'sort_order' => $index,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->values();

        DB::transaction(function () use ($rows) {
            MapPopupLocation::query()->delete();
            MapPopupLocation::query()->insert($rows->all());
        });

        return redirect()
            ->route('admin.map-popup-locations.edit')
            ->with('success', 'Соответствия карты обновлены.');
    }
}