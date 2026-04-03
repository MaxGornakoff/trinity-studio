<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertInteractionMenuItemRequest;
use App\Models\InteractionMenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InteractionMenuItemController extends Controller
{
    public function index()
    {
        $items = InteractionMenuItem::query()
            ->with(['parent:id,title'])
            ->withCount('children')
            ->orderBy('order_column')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'title', 'slug', 'order_column', 'is_active']);

        $itemsById = $items->keyBy('id');
        $levels = [];

        $resolveLevel = function (InteractionMenuItem $item) use (&$resolveLevel, &$levels, $itemsById): int {
            if (isset($levels[$item->id])) {
                return $levels[$item->id];
            }

            if (!$item->parent_id) {
                return $levels[$item->id] = 1;
            }

            $parent = $itemsById->get($item->parent_id);

            if (!$parent) {
                return $levels[$item->id] = 1;
            }

            return $levels[$item->id] = $resolveLevel($parent) + 1;
        };

        return Inertia::render('Admin/InteractionMenuItems/Index', [
            'items' => $items
                ->map(fn (InteractionMenuItem $item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'order_column' => $item->order_column,
                    'is_active' => $item->is_active,
                    'parent_title' => $item->parent?->title,
                    'level' => $resolveLevel($item),
                    'children_count' => $item->children_count,
                ])
                ->values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/InteractionMenuItems/Create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(UpsertInteractionMenuItemRequest $request)
    {
        InteractionMenuItem::create($this->normalizedData($request->validated()));

        return redirect()
            ->route('admin.interaction-menu-items.index')
            ->with('success', 'Пункт меню добавлен.');
    }

    public function edit(InteractionMenuItem $interactionMenuItem)
    {
        return Inertia::render('Admin/InteractionMenuItems/Edit', [
            'item' => $interactionMenuItem,
            'parentOptions' => $this->parentOptions($interactionMenuItem),
        ]);
    }

    public function update(UpsertInteractionMenuItemRequest $request, InteractionMenuItem $interactionMenuItem)
    {
        $interactionMenuItem->update($this->normalizedData($request->validated()));

        return redirect()
            ->route('admin.interaction-menu-items.index')
            ->with('success', 'Пункт меню обновлён.');
    }

    public function destroy(InteractionMenuItem $interactionMenuItem)
    {
        if ($interactionMenuItem->children()->exists()) {
            return redirect()
                ->route('admin.interaction-menu-items.index')
                ->with('error', 'Сначала удалите или перенесите дочерние пункты.');
        }

        $interactionMenuItem->delete();

        return redirect()
            ->route('admin.interaction-menu-items.index')
            ->with('success', 'Пункт меню удалён.');
    }

    private function normalizedData(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug(Str::transliterate($data['title']));
        }

        $data['parent_id'] = isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;
        $data['order_column'] = (int) ($data['order_column'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function parentOptions(?InteractionMenuItem $currentItem = null): array
    {
        $items = InteractionMenuItem::query()
            ->orderBy('order_column')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'title']);

        $excludedIds = $currentItem
            ? [$currentItem->id, ...$currentItem->descendantIds()]
            : [];

        $grouped = $items->groupBy(fn (InteractionMenuItem $item) => $item->parent_id ?? 'root');

        return $this->flattenParentOptions($grouped, $excludedIds);
    }

    private function flattenParentOptions(Collection $grouped, array $excludedIds, ?int $parentId = null, int $level = 1): array
    {
        $key = $parentId ?? 'root';
        $branch = $grouped->get($key, collect());
        $options = [];

        foreach ($branch as $item) {
            if (in_array($item->id, $excludedIds, true)) {
                continue;
            }

            $options[] = [
                'id' => $item->id,
                'title' => str_repeat('— ', max(0, $level - 1)) . $item->title,
                'level' => $level,
            ];

            array_push($options, ...$this->flattenParentOptions($grouped, $excludedIds, $item->id, $level + 1));
        }

        return $options;
    }
}
