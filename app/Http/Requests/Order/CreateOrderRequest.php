<?php

namespace App\Http\Requests\Order;

use App\Exceptions\ApiResponseException;
use App\Http\Response\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'email' => 'email'
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'email alanı geçerli bir email adresi olmalı'
        ];
    }
}
