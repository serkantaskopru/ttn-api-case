<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $hidden = ['id','user_id', 'created_at', 'updated_at'];
    protected $fillable = [
      'code',
      'min_cart_amount',
      'discount_amount',
      'discount_percentage',
      'discount_type',
      'type',
      'product_ids',
      'expiration_date',
      'usage_limit',
    ];

    protected array $dates = [
        'expiration_date',
    ];
}
