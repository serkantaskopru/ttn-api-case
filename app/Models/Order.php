<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    use HasFactory;

    protected $hidden = ['id','user_id', 'created_at', 'updated_at'];
    protected $fillable = [
      'user_id',
      'order_number',
      'sub_total',
      'total'
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }

    public function fees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdditionalFee::class);
    }

    public function coupon(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public static function boot()
    {
        parent::boot();

        static::updated(function ($order) {
            // Order modeli güncellendiğinde önbelleği sıfırla
            Cache::forget('order_' . $order->order_number);
        });
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['urunler'] = $this->items ?? [];
        $array['indirimler'] = $this->offers ?? [];
        $array['ek_ucretler'] = $this->fees ?? [];
        $array['kupon'] = $this->coupon ?? [];

        unset($array['offers'], $array['fees'], $array['coupon'], $array['items']);

        return $array;
    }
}
