<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrCreate(array $array, array $array1)
 */
class Basket extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BasketItem::class);
    }
}
