<?php

namespace Database\Seeders;

use App\Models\InteractionMenuItem;
use Illuminate\Database\Seeder;

class InteractionMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'Бриф',
                'slug' => 'brif',
                'order_column' => 0,
            ],
            [
                'title' => 'Аналитика',
                'slug' => 'analitika',
                'order_column' => 1,
            ],
            [
                'title' => 'Стратегия',
                'slug' => 'strategiya',
                'order_column' => 2,
            ],
            [
                'title' => 'Дизайн',
                'slug' => 'dizayn',
                'order_column' => 3,
            ],
            [
                'title' => 'Разработка',
                'slug' => 'razrabotka',
                'order_column' => 4,
            ],
            [
                'title' => 'Запуск',
                'slug' => 'zapusk',
                'order_column' => 5,
            ],
        ];

        foreach ($items as $item) {
            InteractionMenuItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'parent_id' => null,
                    'title' => $item['title'],
                    'order_column' => $item['order_column'],
                    'is_active' => true,
                ]
            );
        }
    }
}
