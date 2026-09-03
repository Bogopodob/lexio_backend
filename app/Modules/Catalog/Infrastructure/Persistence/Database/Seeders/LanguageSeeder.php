<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'sort' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'is_active' => true,
                'sort' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'is_active' => true,
                'sort' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'is_active' => true,
                'sort' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'is_active' => true,
                'sort' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Language::query()->insert($items);
    }
}
