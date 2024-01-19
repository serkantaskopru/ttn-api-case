<?php

namespace App\Http\Services;

use App\Models\Basket;
use App\Models\BasketItem;

class BasketService
{

    private $user = null;
    private $basket = null;
    protected CouponService $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->user = auth('sanctum')->user();
        $this->couponService = $couponService;

        if(!is_null($this->user))
        $this->basket = $this->getBasket();
    }

    /*
     * Kullanıcıya ait sepeti alıyoruz
     * eğer daha önce sepet oluşturmamışsa yeni sepet oluşturuyoruz
     * */
    public function getBasket()
    {
        return Basket::firstOrCreate(
            ['user_id' => $this->user->id],
            ['user_id' => $this->user->id]
        );
    }

    /*
     * Sepete ait öğeleri al
     * */
    public function getBasketDetails()
    {
        // Eğer sepette herhangi bir ürün yoksa boş bir dizi döndür
        if (is_null($this->basket->items))
            return [];

        $coupon = $this->couponService->getStoredCoupon();

        $basketItems = [];

        foreach ($this->basket->items as $basketItem) {

            // Bu öğeye ait ürün bulunamadıysa es geç
            if (is_null($basketItem->product))
                continue;

            $product = $basketItem->product;

            // Ürün çeşidi mevcutsa json türünde döndür yoksa dizi türünde döndür
            $flavor_notes = is_null($product->flavor_notes) ? [] : json_decode($product->flavor_notes);

            // Ürünün detaylarını alıyoruz
            $productDetails = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'discounted_price' => $product->discounted_price,
                'origin' => $product->origin,
                'roast_level' => $product->roast_level,
                'flavor_notes' => $flavor_notes,
                'category' => $product->category->name ?? '#',
            ];
            if (!is_null($coupon)
                && $this->greaterOrEqualThanSubtotal($coupon->min_cart_amount)
                && !is_null($coupon->product_ids)
                && $coupon->type === 'product_specific'
            ) {

                $productIds = json_decode($coupon->product_ids);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Sepetteki ürün kupon indiriminden faydalanamazsa es geç
                    if (in_array($basketItem->product_id, $productIds)) {
                        if ($coupon->discount_type == 'amount') {
                            $productDetails['discounted_price'] = $productDetails['price'] - ($coupon->discount_amount ?? 0);
                        }

                        if ($coupon->discount_type == 'percentage' && $coupon->discount_percentage > 0) {
                            $productDetails['discounted_price'] = $productDetails['price'] - ($product->price * ($coupon->discount_percentage / 100));
                        }
                    }
                }
            }

            // Sepet öğesine ait bilgileri alıyoruz
            $discounted_total = ($basketItem->quantity * $productDetails['discounted_price']);
            $item = [
                'id' => $basketItem->id,
                'amount' => $basketItem->quantity,
                'product' => $productDetails,
                'total' => $basketItem->quantity * $product->price,
                'discounted_total' => $discounted_total > 0 ? $discounted_total : $basketItem->quantity * $product->price,
            ];

            $basketItems[] = $item;
        }

        $gift_product = null;
        if($this->getTotal() > 3000){
            $gift_product = '1 KG Hediye Kahve';
        }

        $data = [
            'ara_toplam' => $this->getSubTotal(),
            'indirim' => $this->getDiscount(),
            'kargo_bedeli' => $this->getShipRate(),
            'kupon_indirimi' => $this->getOffers(),
            'toplam' => $this->getTotal(),
            'hediye' => $gift_product,
            'indirimler' => $this->getOffersDetails(),
            'kupon' => $this->couponService->getStoredCoupon() ?? null,
            'urunler' => $basketItems
        ];
        return $data;
    }

    /*
     * Ürün sepette mevcut mu kontrol ediyoruz
     * */
    public function isProductAvailableInBasket($product)
    {
        $basket_item = BasketItem::whereBelongsTo($product)->whereBelongsTo($this->basket)->first();
        return !is_null($basket_item);
    }

    /*
     * Sepete belirtilen üründen belirtilen miktarda ekliyoruz
     * */
    public function addProduct($product, $amount): bool
    {
        if ($amount <= 0) return false;

        $basket_item = BasketItem::create([
            'basket_id' => $this->basket->id,
            'product_id' => $product->id,
            'quantity' => $amount,
        ]);

        return !is_null($basket_item);
    }

    /*
     * Belirtilen üründen belirtilen adet kadar silinebilir mi kontrol ediyoruz
     * */
    public function productCanRemovable($product, $amount)
    {
        if ($amount <= 0) return false;

        $basket_item = BasketItem::whereBelongsTo($product)->whereBelongsTo($this->basket)->first();

        return $amount <= $basket_item->quantity;
    }

    /*
     * Ürünün adeti kadar silme yapıyoruz
     * */
    public function removeProduct($product, $amount): int
    {
        $basket_item = BasketItem::whereBelongsTo($product)->whereBelongsTo($this->basket)->first();
        $basket_item_amount = $basket_item->quantity;

        // Eğer ürün adeti 0 olursa ürünü tamamen siliyoruz
        if ($basket_item_amount - $amount > 0) {
            $basket_item->decrement('quantity', $amount);
            return 1;
        } else {
            $basket_item->delete();
        }

        return 2;
    }

    /*
     * Sepetin ara toplamını alıyoruz
     * */
    public function getSubTotal(): float|int
    {
        $basket_items = BasketItem::whereBelongsTo($this->basket)->get();

        $totalPrice = 0;

        foreach ($basket_items as $basket_item) {
            if ($basket_item->product && $basket_item->product->price) {
                $totalPrice += $basket_item->product->price * $basket_item->quantity;
            }
        }

        return number_format($totalPrice, 2, '.', '');
    }

    /*
     * Sepetin indirim oranını al
     * */
    public function getDiscountRate(): int
    {
        $sub_total = $this->getSubTotal();
        return match (true) {
            $sub_total > 3000 => 25,
            $sub_total > 2000 => 20,
            $sub_total > 1500 => 15,
            $sub_total > 1000 => 10,
            default => 0,
        };
    }

    /*
     * Sepetin indirim tutarını al
     * */
    public function getDiscount(): float|int
    {
        $discountRate = $this->getDiscountRate();
        $sub_total = $this->getSubTotal();
        $total = $sub_total * ($discountRate / 100);

        return number_format($total, 2, '.', '');
    }

    /*
     * Kargo ücretini al
     * */
    public function getShipRate(): float|int
    {
        if($this->getSubTotal() <= 0){
            return 0;
        }

        $sub_total = $this->getSubTotal() - $this->getDiscount();

        if ($sub_total < 500) {
            return 54.99;
        }
        return 0;
    }

    /*
     * Sepette geçerli kupon varsa indirim tutarını al
     * Kupon ürünleri kapsıyorsa her bir ürün adeti için indirim yapar
     * */
    public function getOffers(): float|int
    {
        $coupon = $this->couponService->getStoredCoupon();
        $sub_total = $this->getSubTotal();

        if (is_null($coupon)) {
            return 0;
        }

        // Eğer ara toplam kupon gereksinimini karşılamıyorsa es geç
        if (!$this->greaterOrEqualThanSubtotal($coupon->min_cart_amount)) {
            return 0;
        }

        if ($coupon->type === 'global') {
            // Kupon sepete indirim olarak yansıyorsa

            if ($coupon->discount_type == 'amount') {
                return $coupon->discount_amount ?? 0;
            }
            if ($coupon->discount_type == 'percentage' && $coupon->discount_percentage > 0) {
                return $sub_total * ($coupon->discount_percentage / 100);
            }
        } elseif ($coupon->type === 'product_specific') {
            // Kupon ürünlere indirim olarak yansıyorsa

            $totalProductDiscount = 0;
            if ($coupon->product_ids !== null) {
                $productIds = json_decode($coupon->product_ids);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $basket_items = $this->basket->items;

                    if (is_null($basket_items)) {
                        return 0;
                    }

                    foreach ($basket_items as $item) {
                        // Sepetteki ürün kupon indiriminden faydalanamazsa es geç
                        if (!in_array($item->product_id, $productIds)) {
                            continue;
                        }

                        // Sepetteki öğeye ait ürün mevcut değilse es geç
                        if (is_null($item->product)) {
                            continue;
                        }

                        $product = $item->product;

                        if ($coupon->discount_type == 'amount') {
                            $totalProductDiscount += $coupon->discount_amount ?? 0 * $item->quantity;
                        }

                        if ($coupon->discount_type == 'percentage' && $coupon->discount_percentage > 0) {
                            $totalProductDiscount += ($product->price * ($coupon->discount_percentage / 100)) * $item->quantity;
                        }
                    }

                    return number_format($totalProductDiscount, 2, '.', '');
                }
            }
        }

        return 0;
    }

    /*
     * Sepete ait ek ücretleri al
     * */
    public function getOffersDetails()
    {
        $fees = [];
        $discount = $this->getDiscount();
        $coupon_discount = $this->getOffers();
        if ($discount) {
            $fees[] = [
                'aciklama' => 'Sepette %' . $this->getDiscountRate() . ' indirim',
                'tutar' => -$discount,
            ];
        }
        if ($coupon_discount) {
            $fees[] = [
                'aciklama' => 'Sepette Ek ' . $this->getOffers() . ' TL indirim',
                'tutar' => -$coupon_discount,
            ];
        }

        return $fees;
    }

    /*
     * Sepetin net toplam tutarını al
     * */
    public function getTotal(): float|int
    {
        $totalWithoutFormat = $this->getSubTotal();
        $totalWithoutFormat += $this->getShipRate();
        $totalWithoutFormat -= $this->getOffers();
        $totalWithoutFormat -= $this->getDiscount();

        return number_format($totalWithoutFormat, 2, '.', '');
    }

    /*
     * Sepet aratoplamı belirtilen sayıya eşit veya büyükse true döndür
     * */
    public function greaterOrEqualThanSubtotal(int|float $amount): bool
    {
        return $this->getSubTotal() >= $amount;
    }

    /*
     * Sepeti temizle
     * */
    public function clearBasket(): bool
    {
        $basket_items = $this->basket->items;
        if(is_null($basket_items)){
            return false;
        }

        $basket_items->each->delete();
        return true;
    }
}
