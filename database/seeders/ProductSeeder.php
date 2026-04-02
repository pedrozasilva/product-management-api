<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();

        Product::factory(30)->create([
            'user_id' => fn () => $users->random()->id,
            'category_id' => fn () => $categories->random()->id,
        ]);
    }
}
