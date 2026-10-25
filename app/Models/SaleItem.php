<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['sale_id', 'name', 'qty', 'price'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
