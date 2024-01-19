<?php

namespace App\Http\Services;

use App\Jobs\SendOrderMailJob;
use App\Models\AdditionalFee;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOffer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService{

    private ?\Illuminate\Contracts\Auth\Authenticatable $user;
    protected ProductService $productService;
    protected CouponService $couponService;
    protected BasketService $basketService;

    public function __construct(CouponService $couponService, BasketService $basketService, ProductService $productService)
    {
        $this->user = auth('sanctum')->user();
        $this->couponService = $couponService;
        $this->basketService = $basketService;
        $this->productService = $productService;
    }

    /*
     * Yeni bir sipariş numarası oluştur
     * */
    public function generateOrderNumber(): string
    {
        $characters = '0123456789';
        do {
            $orderNumber = '';
            for ($i = 0; $i < 12; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $orderNumber .= $characters[$index];
            }
        } while ($this->orderNumberExists($orderNumber));

        return $orderNumber;
    }

    /*
     * Sipariş numarasına ait sipariş mevcut mu
     * */
    private function orderNumberExists($order_number): bool
    {
        return !is_null($this->getOrder($order_number));
    }

    /*
     * Sipariş verisini al
     * */
    private function getOrder($order_number) {
        // Önbellekte varsa, önbellekten al
        $cachedOrder = Cache::get('order_' . $order_number);

        if ($cachedOrder) {
            return $cachedOrder;
        }

        // Önbellekte yoksa, veritabanından al ve önbelleğe ekle
        $order = Order::where('order_number', $order_number)->first();

        Cache::put('order_' . $order_number, $order, now()->addHour());

        return $order;
    }

    /*
     * Sipariş detaylarını al
     * */
    public function getOrderDetails($order_number): array
    {
        $order = $this->getOrder($order_number);
        if(is_null($order)){
            return [];
        }

        return $order->toArray();
    }

    /*
     * Yeni sipariş oluştur
     * */
    public function createOrder(string|null $email): array
    {
        if(is_null($this->user)){
            return [
                'success' => false,
                'message' => 'Oturum bulunamadı'
            ];
        }

        $order_number = $this->generateOrderNumber();
        $coupon = $this->couponService->getStoredCoupon();
        $basket_details = $this->basketService->getBasketDetails();

        $basket_items = $basket_details['urunler'];

        if(is_null($basket_items) || !is_array($basket_items) || count($basket_items) <= 0){
            return [
                'success' => false,
                'message' => 'Sepetinizde ürün bulunmuyor'
            ];
        }

        foreach($basket_items as $item){
            $product = $this->productService->getProductFromId($item['product']['id']);
            if(is_null($product)){
                return [
                    'success' => false,
                    'message' => 'Sepetinizdeki bir ürün artık mevcut değil'
                ];
            }
            if(!$this->productService->isProductStockAvailable($product,$item['amount'])){
                return [
                    'success' => false,
                    'message' => $product->name . " ürününden en fazla " . $product->stock_quantity . " adet satın alabilirsiniz."
                ];
            }
        }

        DB::beginTransaction();
        try{

            $order = Order::create([
                'user_id' => $this->user->id,
                'order_number' => $order_number,
                'sub_total' => $basket_details['ara_toplam'],
                'total' => $basket_details['toplam']
            ]);

            foreach($basket_items as $item){

                $product = $item['product'];

                if(!$this->productService->decreaseStock($product['id'], $item['amount'])){
                    return [
                        'success' => false,
                        'message' => $product['name'] . " stoğu karşılanamıyor."
                    ];
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'quantity' => $item['amount'],
                    'price' => $product['price'],
                    'discounted_price' => $product['discounted_price'] ?? $product['price'],
                ]);

            }

            // Hediye kahve ekle
            if($basket_details['toplam'] > 3000){
                $gift_product = $this->productService->getProductFromName('1 KG Hediye Kahve');
                if(!is_null($gift_product) && $gift_product->stock_quantity > 0){
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $gift_product->id,
                        'quantity' => 1,
                        'price' => $gift_product->price,
                        'discounted_price' => 0,
                    ]);
                }
            }

            $discounts = $basket_details['indirimler'];

            if(!is_null($discounts) && is_array($discounts)){
                foreach($discounts as $discount){
                    if(is_null($discount['tutar'])){
                        continue;
                    }

                    $amount = abs($discount['tutar']);

                    if($amount <= 0){
                        continue;
                    }

                    OrderOffer::create([
                        'order_id' => $order->id,
                        'description' => $discount['aciklama'] ?? '',
                        'amount' => $amount
                    ]);
                }
            }

            $shipping_cost = $basket_details['kargo_bedeli'];

            if(!is_null($shipping_cost)){
                AdditionalFee::create([
                    'order_id' => $order->id,
                    'description' => 'Kargo Ücreti',
                    'amount' => $shipping_cost
                ]);
            }

            if(!is_null($coupon)){
                CouponUsage::create([
                    'user_id' => $this->user->id,
                    'order_id' => $order->id,
                    'coupon_id' => $coupon->id,
                ]);
            }

            if(!$this->basketService->clearBasket()){
                return [
                    'success' => false,
                    'message' => 'Sepet temizlenirken bir hata oluştu'
                ];
            }

            DB::commit();

            $order_details = $this->getOrderDetails($order_number);

            $this->couponService->clearCouponCodeFromSession();

            if(!is_null($email)){
                SendOrderMailJob::dispatch($email, $order_details->toArray());
            }

            return [
                'success' => true,
                'siparis_no' => $order_number,
                'tutar' => $order->total . ' TL',
                'siparis_detay' => $order_details
            ];
        }catch (\Exception $exception){
            Log::error($exception);
            DB::rollBack();
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    /*
     * Sipariş ürünü barındırıyor mu
     * */
    public function isOrderHaveProduct($order_number, $product_id): bool
    {
        $order = $this->getOrder($order_number);
        $product = $this->productService->getProductFromId($product_id);

        if(is_null($order)){
            return false;
        }

        if(is_null($product)){
            return false;
        }

        return OrderItem::whereBelongsTo($order)->whereBelongsTo($product)->exists();
    }

    /*
     * Siparişteki ürün adetini azalt
     * */
    public function decreaseQuantity($order_number, $product_id, $quantity): bool
    {
        $order = $this->getOrder($order_number);

        $product = $this->productService->getProductFromId($product_id);

        if(is_null($product)){
            return false;
        }

        $order_item = OrderItem::whereBelongsTo($order)->whereBelongsTo($product)->first();

        if(is_null($order_item)){
            return false;
        }

        if($order_item->quantity <= $quantity){
            return false;
        }

        if(!$this->productService->increaseStock($product_id, $quantity)){
            return false;
        }

        $order_item->decrement('quantity', $quantity);

        $order->update([
            'sub_total' => $order->sub_total - ($order_item->price * $quantity),
            'total' => $order->total - ($order_item->discounted_price * $quantity),
        ]);

        return true;
    }

    /*
     * Siparişteki ürün adetini arttır
     * */
    public function increaseQuantity($order_number, $product_id, $quantity): bool
    {
        $order = $this->getOrder($order_number);

        $product = $this->productService->getProductFromId($product_id);

        if(is_null($product)){
            return false;
        }

        $order_item = OrderItem::whereBelongsTo($order)->whereBelongsTo($product)->first();

        if(is_null($order_item)){
            return false;
        }

        if(!$this->productService->decreaseStock($product_id, $quantity)){
            return false;
        }

        $order_item->increment('quantity', $quantity);

        $order->update([
            'sub_total' => $order->sub_total + ($order_item->price * $quantity),
            'total' => $order->total + ($order_item->discounted_price * $quantity),
        ]);

        return true;
    }

    /*
     * Siparişten ürün sil
     * */
    public function removeProductFromOrder($order_number, $product_id): bool
    {
        $order = $this->getOrder($order_number);

        $product = $this->productService->getProductFromId($product_id);

        if(is_null($product)){
            return false;
        }

        $order_item = OrderItem::whereBelongsTo($order)->whereBelongsTo($product)->first();

        if(is_null($order_item)){
            return false;
        }

        if(!$this->productService->increaseStock($product_id, $order_item->quantity)){
            return false;
        }

        $order->update([
            'sub_total' => max(0,$order->sub_total - ($order_item->price * $order_item->quantity)),
            'total' => max(0,$order->total - ($order_item->discounted_price * $order_item->quantity)),
        ]);

        $order_item->delete();

        return true;
    }
}
