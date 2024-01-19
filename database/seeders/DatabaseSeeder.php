<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Api için test kullanıcı bilgileri
        $users = [
            [
                'name' => 'Api Tester',
                'email' => 'info@example.com',
                'password' => Hash::make('123456')
            ],
            [
                'name' => 'Sanctum User 1',
                'email' => 'sanctum@laravel.com',
                'password' => Hash::make('123456')
            ],
        ];

        // Listedeki kullanıcıların tanımlanması
        foreach ($users as $user) {
            User::insert($user);
        }

        // Kategori ekle
        $category = Category::create(['name' => 'Kahve']);

        // Ürünlerin json dosyasını oku ve değişkene aktar
        $jsonFilePath = storage_path('app/json/products.json');
        $jsonContents = File::get($jsonFilePath);
        $products = json_decode($jsonContents, true);

        // Eğer json_decode başarısız olursa, hata kontrolü ver
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON dosyasını okurken bir hata oluştu: ' . json_last_error_msg());
        }

        // Ürünleri ekle
        foreach ($products as $productData) {
            // 'flavor_notes' anahtarı $productData dizisinde var mı
            $flavorNotes = isset($productData['flavor_notes']) ? $productData['flavor_notes'] : null;

            Product::create([
                'name' => $productData['title'],
                'category_id' => $category->id,
                'description' => $productData['description'],
                'price' => $productData['price'],
                'stock_quantity' => $productData['stock_quantity'],
                'origin' => $productData['origin'],
                'roast_level' => $productData['roast_level'],
                'flavor_notes' => $flavorNotes !== null ? json_encode($flavorNotes) : null,
            ]);
        }

        // Hediye kahve ürünü
        Product::create([
            'name' => '1 KG Hediye Kahve',
            'category_id' => 1,
            'description' => 'Bu ürün hediyedir, para ile satılamaz',
            'price' => 24.99,
            'stock_quantity' => 99,
            'origin' => 'Türkiye',
            'roast_level' => 'Hafif',
            'flavor_notes' => null,
        ]);

        $coupons = [
            [
                'code' => 'TTN2024TTT001', // 500 tl ve üzeri 50 tl indirim
                'min_cart_amount' => 500, // Minimum sepet tutarı 500 TL
                'discount_amount' => 50, // 50 TL indirim
                'discount_percentage' => null,
                'discount_type' => 'amount',
                'type' => 'global',
                'product_ids' => null,
                'expiration_date' => Carbon::now()->addMinutes(30), // 30 dakika geçerlilik
                'usage_limit' => 5,
            ],
            [
                'code' => 'TTN2024TTT002', // Harika kahve, Yoğun lezzet, Hafif sipariş ürünlerinde 100tl ve üzeri siparişlerde geçerli %10 indirim
                'min_cart_amount' => 100,
                'discount_amount' => null,
                'discount_percentage' => 10,
                'discount_type' => 'percentage',
                'type' => 'product_specific',
                'product_ids' => json_encode([1, 2, 3]),
                'expiration_date' => Carbon::now()->addDays(7),
                'usage_limit' => 10,
            ],
            [
                'code' => 'TTN20TTTTT001', // Harika kahve ürününde geçerli 5tl indirim
                'min_cart_amount' => 0, // Sepet tutarı önemsiz
                'discount_amount' => 5, // 5 TL indirim
                'discount_percentage' => null,
                'discount_type' => 'amount',
                'type' => 'product_specific',
                'product_ids' => json_encode([1]),
                'expiration_date' => Carbon::now()->addDays(30),
                'usage_limit' => 945,
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
    }
}
