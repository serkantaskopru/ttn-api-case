<?php

namespace App\Http\Controllers;

use App\Http\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Kullanıcı girişi ve token oluşturma işlemi
     *
     * @param [string] email
     * @param [string] password
     * @return [string] token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [string] success
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            // Hataları al
            $errors = $validator->errors();

            // Hataları string'e dönüştür
            $errorString = '';
            foreach ($errors->all() as $error) {
                $errorString .= $error . "\n";
            }

            // Hataları yanıt olarak döndür
            return new ApiResponse($errorString, 10001);
        }
        $credentials = request(['email', 'password']);

        // Kullanıcı bilgilerini kontrol et
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $message['token'] = $user->createToken('api-access')->plainTextToken;
            $message['token_type'] = 'Bearer';
            $message['expires_at'] = Carbon::parse(Carbon::now()->addWeeks(1))->toDateTimeString();
            $message['success'] = 'Kullanıcı Girişi Başarılı';

            return new ApiResponse($message, 10002, ApiResponse::$success);
        } else {
            return new ApiResponse("Kullanıcı bilgileri hatalı", 10000);
        }
    }

    public function loginPage(Request $request){
        return new ApiResponse("Token geçerli değil", 10003);
    }
}
