<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LanguageSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DemoContentSeeder::class);
    }
}
