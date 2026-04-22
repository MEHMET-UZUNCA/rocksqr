<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PDF'den veri çekilemedi, örnek veri ekliyorum
        // Gerçek veriyi manuel ekleyin veya PDF parser'ı düzeltin

        $categories = [
            ['name' => 'Yiyecekler', 'description' => 'Lezzetli yiyecekler'],
            ['name' => 'İçecekler', 'description' => 'Serinletici içecekler'],
            ['name' => 'Tatlılar', 'description' => 'Tatlı seçenekleri'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
            ]);
        }

        $products = [
            ['name' => 'Hamburger', 'description' => 'Dana eti ile', 'price' => 25.00, 'category_id' => 1],
            ['name' => 'Pizza', 'description' => 'Peynirli pizza', 'price' => 30.00, 'category_id' => 1],
            ['name' => 'Kola', 'description' => 'Soğuk kola', 'price' => 5.00, 'category_id' => 2],
            ['name' => 'Çay', 'description' => 'Sıcak çay', 'price' => 3.00, 'category_id' => 2],
            ['name' => 'Baklava', 'description' => 'Antep fıstıklı', 'price' => 15.00, 'category_id' => 3],
        ];

        foreach ($products as $prod) {
            Product::create($prod);
        }
    }
}
