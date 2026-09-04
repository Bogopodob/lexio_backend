<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\DatabaseSeeder as CatalogDatabaseSeeder;
use App\Modules\Learning\Infrastructure\Persistence\Database\Seeders\DatabaseSeeder as LearningDatabaseSeeder;
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
        $this->call(CatalogDatabaseSeeder::class);
        $this->call(LearningDatabaseSeeder::class);
    }
}
