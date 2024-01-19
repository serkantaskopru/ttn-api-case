<?php

namespace App\Http\Controllers;

use App\Http\Requests\Coupon\ApplyCouponRequest;
use App\Http\Requests\Coupon\RemoveCouponRequest;
use App\Http\Response\ApiResponse;
use App\Http\Services\BasketService;
use App\Http\Services\CouponService;
use App\Models\Coupon;

class CouponController extends Controller
{
    protected CouponService $couponService;
    protected BasketService $basketService;

    public function __construct(CouponService $couponService, BasketService $basketService)
    {
        $this->couponService = $couponService;
        $this->basketService = $basketService;
    }

    /*
     * Kupon kodunu uygula
     * */
    public function applyCode(ApplyCouponRequest $request): ApiResponse
    {
        $coupon_code = $request->get('coupon_code');
        $coupon = Coupon::where('code',$coupon_code)->first();

        if(!$this->couponService->isValidAlgorithm($coupon_code)){
            return new ApiResponse("Bu kupon kodu geçersiz algoritmaya sahip", 10021, ApiResponse::$error);
        }

        if(!$this->couponService->isDateValid($coupon)){
            return new ApiResponse("Bu kuponun kullanım tarihi geçmiş", 10022, ApiResponse::$error);
        }

        if(!$this->couponService->isUsageValid($coupon)){
            return new ApiResponse("Bu kuponun kullanım limiti kalmamış", 10023, ApiResponse::$error);
        }

        if(!$this->basketService->greaterOrEqualThanSubtotal($coupon->min_cart_amount)){
            return new ApiResponse("Sepetiniz bu kuponun min tutarını karşılamıyor", 10024, ApiResponse::$error);
        }

        if(!$this->couponService->isCouponApplicableToBasket($coupon, $this->basketService)){
            return new ApiResponse("Bu kupon sepetinize uygulanamaz", 10025, ApiResponse::$error);
        }

        $this->couponService->storeCouponCodeInSession($coupon_code);

        return new ApiResponse("Kupon kodu başarıyla uygulandı", 10029, ApiResponse::$success);
    }

    /*
     * Kupon kodunu kaldır
     * */
    public function removeCode(RemoveCouponRequest $request): ApiResponse
    {
        if(is_null($this->couponService->getCouponCodeFromSession())){
            return new ApiResponse("Sepetinize tanımlı herhangi bir kupon kodu yok", 10026, ApiResponse::$error);
        }
        if($this->couponService->getCouponCodeFromSession() != $request->get('coupon_code')){
            return new ApiResponse("Bu kupon kodu sepetinize uygulanmadı", 10027, ApiResponse::$error);
        }

        $this->couponService->clearCouponCodeFromSession();

        return new ApiResponse("Kupon kodu başarıyla kaldırıldı", 10028, ApiResponse::$success);
    }
}
