<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $hidden = ['category_id', 'created_at', 'updated_at'];
    protected $fillable = [
      'price',
      'category_id',
      'stock_quantity',
      'name',
      'origin',
      'roast_level',
      'flavor_notes',
      'description'
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['category'] = $this->category->toArray();

        return $array;
    }
}
