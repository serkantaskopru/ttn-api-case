<?php

namespace App\Http\Services;

use App\Models\Product;

class ProductService{

    public function getProductFromName($product_name){
        return Product::where('name',$product_name)->first();
    }

    public function getProductFromId($product_id){
        return Product::find($product_id);
    }

    public function decreaseStock($product_id, $amount): bool
    {
        $product = $this->getProductFromId($product_id);
        if(is_null($product)){
            return false; // Aranan ürün mevcut değil, işlem başarısız
        }

        if ($amount > $product->stock_quantity) {
            return false; // İstenen miktarda ürün stokta yok, işlem başarısız
        }
        $product->decrement('stock_quantity',$amount);
        return true;
    }

    public function increaseStock($product_id, $amount): bool
    {
        $product = $this->getProductFromId($product_id);
        if(is_null($product)){
            return false; // Aranan ürün mevcut değil, işlem başarısız
        }

        $product->increment('stock_quantity',$amount);
        return true;
    }

    public function isProductStockAvailable($product, $amount): bool
    {
        return $product->stock_quantity >= $amount;
    }
}
