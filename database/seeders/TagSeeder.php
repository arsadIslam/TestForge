<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    private const TAGS = [
        'Previous Year', 'Conceptual', 'Numerical', 'Tricky',
        'Formula Based', 'Important', 'Revision', 'Diagram Based',
    ];

    public function run(): void
    {
        foreach (self::TAGS as $name) {
            Tag::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
    }
}
