<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\DecreaseQuantityRequest;
use App\Http\Requests\Order\IncreaseQuantityRequest;
use App\Http\Requests\Order\OrderDetailsRequest;
use App\Http\Requests\Order\RemoveOrderProductRequest;
use App\Http\Response\ApiResponse;
use App\Http\Services\OrderService;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /*
     * Sipariş oluştur
     * */
    public function createOrder(CreateOrderRequest $request){
        $data = $this->orderService->createOrder($request->get('email'));

        if($data['success'] != true){
            return new ApiResponse($data['message'], 10050, ApiResponse::$error);
        }

        unset($data['success']);

        return [
            'success' => true,
            'message' => 'İşlem başarılı',
            'code' => 10040,
            'status' => 200,
            'data' => $data
        ];
    }

    /*
     * Sipariş detaylarını getir
     * */
    public function orderDetails(OrderDetailsRequest $request){
        $order_number = $request->get('order_number');
        $data = $this->orderService->getOrderDetails($order_number);

        return [
            'success' => true,
            'message' => 'İşlem başarılı',
            'code' => 10040,
            'status' => 200,
            'data' => $data
        ];
    }

    /*
     * Siparişteki ürünün stoğunu arttır
     * */
    public function increaseQuantity(IncreaseQuantityRequest $request): ApiResponse
    {
        $order_number = $request->get('order_number');
        $product_id = $request->get('product_id');
        $quantity = $request->get('quantity');

        if($quantity <= 0){
            return new ApiResponse("Ürün miktarını arttırmak için en az 1 adet girmelisiniz", 10041, ApiResponse::$error);
        }

        if(!$this->orderService->isOrderHaveProduct($order_number,$product_id)){
            return new ApiResponse("Bu ürün bu siparişte mevcut değil", 10042, ApiResponse::$error);
        }

        if(!$this->orderService->increaseQuantity($order_number,$product_id,$quantity)){
            return new ApiResponse("Bu ürünün sayısını daha fazla arttıramazsınız. Ürün stoğu tükendi.", 10043, ApiResponse::$error);
        }

        return new ApiResponse("Ürün miktarı başarıyla arttırıldı", 10044, ApiResponse::$success);
    }

    /*
     * Siparişteki ürünün stoğunu azalt
     * */
    public function decreaseQuantity(DecreaseQuantityRequest $request): ApiResponse
    {
        $order_number = $request->get('order_number');
        $product_id = $request->get('product_id');
        $quantity = $request->get('quantity');

        if($quantity <= 0){
            return new ApiResponse("Ürün miktarını azaltmak için en az 1 adet girmelisiniz", 10041, ApiResponse::$error);
        }

        if(!$this->orderService->isOrderHaveProduct($order_number,$product_id)){
            return new ApiResponse("Bu ürün bu siparişte mevcut değil", 10042, ApiResponse::$error);
        }

        if(!$this->orderService->decreaseQuantity($order_number,$product_id,$quantity)){
            return new ApiResponse("Bu ürünün sayısını daha fazla azaltamazsınız, bunun yerine ürünü tamamen kaldırabilirsiniz.", 10043, ApiResponse::$error);
        }

        return new ApiResponse("Ürün miktarı başarıyla azaltıldı", 10044, ApiResponse::$success);
    }

    /*
     * Siparişteki ürünü kaldır
     * */
    public function removeProduct(RemoveOrderProductRequest $request): ApiResponse
    {
        $order_number = $request->get('order_number');
        $product_id = $request->get('product_id');

        if(!$this->orderService->isOrderHaveProduct($order_number,$product_id)){
            return new ApiResponse("Bu ürün bu siparişte mevcut değil", 10042, ApiResponse::$error);
        }

        if(!$this->orderService->removeProductFromOrder($order_number,$product_id)){
            return new ApiResponse("Bu ürün siparişten kaldırılamıyor.", 10043, ApiResponse::$error);
        }

        return new ApiResponse("Ürün siparişten kaldırıldı", 10044, ApiResponse::$success);
    }
}
