<?php

namespace App\Http\Requests\Order;

use App\Exceptions\ApiResponseException;
use App\Http\Response\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DecreaseQuantityRequest extends FormRequest
{
    /**
     * Gönderilen verilerin doğrulama işlemlerini yapıyoruz
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ApiResponseException($validator->errors(), 10020, ApiResponse::$unprocessable);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|numeric',
            'order_number' => 'required|exists:orders,order_number',
            'product_id' => 'required|exists:order_items,product_id'
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Bir adet belirtin',
            'quantity.number' => 'Adet türü sayı veya rakam olabilir',
            'product_id.required' => 'Bir ürün numarası belirtin',
            'product_id.exists' => 'Bu numaraya ait bir ürün siparişte mevcut değil.',
            'order_number.required' => 'Bir sipariş numarası belirtin',
            'order_number.exists' => 'Bu sipariş numarasına ait bir sipariş mevcut değil.'
        ];
    }
}
