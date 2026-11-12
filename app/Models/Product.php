<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sku', 'price_cents', 'stock', 'reorder_level', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'price_cents' => 'integer', 'stock' => 'integer'];
    }
}
