<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $hidden = ['id','order_id', 'created_at', 'updated_at'];
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'discounted_price',
    ];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['product'] = $this->product->toArray();

        return $array;
    }
}
