<?php

namespace App\Http\Requests\Coupon;

use App\Exceptions\ApiResponseException;
use App\Http\Response\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RemoveCouponRequest extends FormRequest
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
            'coupon_code' => 'required|exists:coupons,code'
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_code.required' => 'Bir kupon kodu belirtin',
            'coupon_code.exists' => 'Bu kupon kodu sistemde mevcut değil.'
        ];
    }
}
