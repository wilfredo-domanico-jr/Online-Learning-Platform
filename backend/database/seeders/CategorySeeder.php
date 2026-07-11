<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed a starter set of top-level course categories.
     */
    public function run(): void
    {
        foreach ([
            'Development',
            'Business',
            'Design',
            'Marketing',
            'IT & Software',
            'Personal Development',
        ] as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
