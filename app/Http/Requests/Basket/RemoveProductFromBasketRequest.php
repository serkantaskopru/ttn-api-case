<?php

namespace App\Http\Requests\Basket;

use App\Exceptions\ApiResponseException;
use App\Http\Response\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RemoveProductFromBasketRequest extends FormRequest
{
    /**
     * Gönderilen verilerin doğrulama işlemlerini yapıyoruz
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ApiResponseException($validator->errors(), 10010, ApiResponse::$unprocessable);
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
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|numeric|gt:0'
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Seçilen ürün sistemde mevcut değil.',
            'amount.required' => 'Miktar zorunludur.',
            'amount.numeric' => 'Miktar sayı olmalıdır.',
            'amount.gt' => 'Miktar 0 dan büyük olmalıdır.'
        ];
    }
}
