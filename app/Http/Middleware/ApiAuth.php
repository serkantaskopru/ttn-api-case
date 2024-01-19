<?php

namespace App\Http\Middleware;

use App\Http\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(is_null($request->user())){
            return \response()->json([
                'success' => false,
                'message' => 'Token geçerli değil',
                'code' => 10001,
                'status' => 200,
            ]);
        }
        dd($request->user());
        return $next($request);
    }
}
