<?php

namespace App\Http\Services;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class CouponService{

    /*
     * Kuponun kullanım limitini kontrol eder
     * */
    public function isUsageValid($coupon): bool
    {
        if(is_null($coupon->usage_limit))
            return false;

        // Kullanım limiti geçerliyse true döndür
        return $coupon->usage_limit > 0;
    }

    /*
     * Kuponun geçerlilik tarihini kontrol eder
     * */
    public function isDateValid($coupon): bool
    {
        if(is_null($coupon->expiration_date))
            return false;

        // Son kullanma tarihi henüz geçmemişse true döndür
        return Carbon::now()->lte($coupon->expiration_date);
    }

    /*
     * Kuponun algoritmasını kontrol eder
     * */
    public function isValidAlgorithm($code): bool
    {
        return preg_match('/^TTN\d+T{3,}\d+$/', $code);
    }

    /*
     * Kupon kullanıcının sepetindeki ürünlere uygulanabilir mi kontrol et
     * */
    public static function isCouponApplicableToBasket($coupon, BasketService $basketService): bool
    {
        $basket = $basketService->getBasket();

        if ($coupon->type === 'product_specific') {
            // Tipi product_specific ise ürün kontrolü yap

            // Kuponun geçerli olduğu ürünleri kontrol et
            if ($coupon->product_ids !== null) {
                $productIds = json_decode($coupon->product_ids);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // JSON dönüşümü hatasız ise devam et
                    $basketProductIds = $basket->items()->pluck('product_id')->toArray();

                    if (count(array_intersect($productIds, $basketProductIds)) === 0) {
                        return false; // Kupon ürünlerine uygunsuzsa false döndür
                    }
                } else {
                    // JSON dönüşümü hatası varsa, gerekli hata işlemlerini yapabilirsiniz.
                    return false;
                }
            }
        }

        return true; // Tüm kontrolleri geçerse true döndür
    }

    /*
     * Oturumdaki kullanılan kuponu al
     * */
    public function getStoredCoupon(){
        return Coupon::where('code',$this->getCouponCodeFromSession())->first();
    }

    /*
     * Yeni kupon kodunu session'a ekle
     * */
    public static function storeCouponCodeInSession($code)
    {
        // Eğer kullanımda mevcut bir kupon kodu varsa, onu unut
        if (Session::has('coupon_code')) {
            Session::forget('coupon_code');
        }

        Session::put('coupon_code', $code);
    }

    /*
     * Session'dan kupon kodunu al
     * */
    public static function getCouponCodeFromSession()
    {
        return Session::get('coupon_code');
    }

    /*
     * Session'dan kupon kodunu temizle
     * */
    public static function clearCouponCodeFromSession()
    {
        Session::forget('coupon_code');
    }
}
