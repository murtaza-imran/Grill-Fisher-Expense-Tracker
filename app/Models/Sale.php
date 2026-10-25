<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = ['date', 'customer', 'payment', 'total', 'note'];
    protected $casts = ['date' => 'date'];

    /** What a sale can be. */
    public const CATEGORIES = ['BBQ', 'Fish', 'Other'];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function summary(): string
    {
        if ($this->items->count() === 1 && $this->items->first()->qty === 1) {
            return $this->items->first()->name;
        }
        return $this->items->map(fn ($i) => "{$i->qty}× {$i->name}")->implode(', ');
    }

    /** BBQ / Fish / Other — older sales made from menu items count as Other. */
    public function category(): string
    {
        $name = $this->items->first()?->name;
        return in_array($name, self::CATEGORIES, true) ? $name : 'Other';
    }

    /** Store plain Y-m-d so date filters ("up to today") include today's entries. */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
