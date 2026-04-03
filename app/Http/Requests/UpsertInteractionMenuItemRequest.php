<?php

namespace App\Http\Requests;

use App\Models\InteractionMenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertInteractionMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentItem = $this->route('interaction_menu_item');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('interaction_menu_items', 'slug')->ignore($currentItem?->id),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:interaction_menu_items,id'],
            'order_column' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parentId = $this->input('parent_id');

            if ($parentId === null || $parentId === '') {
                return;
            }

            /** @var InteractionMenuItem|null $currentItem */
            $currentItem = $this->route('interaction_menu_item');
            $parent = InteractionMenuItem::query()->find($parentId);

            if (!$parent) {
                return;
            }

            if ($currentItem && $parent->id === $currentItem->id) {
                $validator->errors()->add('parent_id', 'Пункт меню не может быть родителем сам для себя.');
                return;
            }

            $level = 2;
            $ancestor = $parent->parent;

            while ($ancestor) {
                if ($currentItem && $ancestor->id === $currentItem->id) {
                    $validator->errors()->add('parent_id', 'Нельзя вложить пункт в собственного потомка.');
                    return;
                }

                $level += 1;

                if ($level > 4) {
                    $validator->errors()->add('parent_id', 'Максимальная глубина меню — 4 уровня.');
                    return;
                }

                $ancestor = $ancestor->parent;
            }
        });
    }
}
