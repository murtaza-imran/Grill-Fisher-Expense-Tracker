<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = ['date', 'customer', 'payment', 'total', 'note'];
    protected $casts = ['date' => 'date'];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function summary(): string
    {
        return $this->items->map(fn ($i) => "{$i->qty}× {$i->name}")->implode(', ');
    }

    /** Store plain Y-m-d so date filters ("up to today") include today's entries. */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
