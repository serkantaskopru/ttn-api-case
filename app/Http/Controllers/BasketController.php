<?php

namespace App\Http\Controllers;

use App\Http\Requests\Basket\AddProductToBasketRequest;
use App\Http\Requests\Basket\RemoveProductFromBasketRequest;
use App\Http\Response\ApiResponse;
use App\Http\Services\BasketService;
use App\Http\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;

class BasketController extends Controller
{
    protected BasketService $basketService;
    protected ProductService $productService;

    public function __construct(BasketService $basketService, ProductService $productService)
    {
        $this->basketService = $basketService;
        $this->productService = $productService;
    }

    /*
     * Sepete ürün ekleme işlemi
     * */
    public function addProductToBasket(AddProductToBasketRequest $request): ApiResponse
    {
        $product_id = $request->get('product_id');
        $amount = $request->get('amount');

        $product = Product::find($product_id);

        if(!$this->productService->isProductStockAvailable($product,$amount)){
            return new ApiResponse("Bu üründen en fazla " . $product->stock_quantity . " adet satın alabilirsiniz.", 10011, ApiResponse::$error);
        }

        if($this->basketService->isProductAvailableInBasket($product)){
            return new ApiResponse("Bu ürün zaten sepetinizde mevcut", 10012, ApiResponse::$error);
        }

        $this->basketService->addProduct($product,$amount);

        return new ApiResponse("Ürün başarıyla sepete eklendi", 10010, ApiResponse::$success);
    }

    /*
     * Sepetten ürün kaldırma işlemi
     * */
    public function removeProductFromBasket(RemoveProductFromBasketRequest $request): ApiResponse
    {
        $product_id = $request->get('product_id');
        $amount = $request->get('amount');

        $product = Product::find($product_id);

        if(!$this->basketService->isProductAvailableInBasket($product)){
            return new ApiResponse("Bu ürün sepetinizde mevcut değil", 10013, ApiResponse::$error);
        }

        if(!$this->basketService->productCanRemovable($product, $amount)){
            return new ApiResponse("Bu üründen belirttiğiniz adet kadar silme yapamazsınız", 10014, ApiResponse::$error);
        }

        $remove_product_stat = $this->basketService->removeProduct($product,$amount);

        $message = match ($remove_product_stat) {
            1 => 'Bu üründen ' . $amount . ' adet silindi',
            2 => 'Bu ürün sepetinizden tamamen kaldırıldı',
        };

        return new ApiResponse($message, 10015, ApiResponse::$success);
    }

    /*
     * Sepetteki ürünlerin verilerini al
     * */
    public function getDetails(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'İşlem başarılı',
            'code' => 10016,
            'status' => 200,
            'data' => $this->basketService->getBasketDetails()
        ];
    }
}
