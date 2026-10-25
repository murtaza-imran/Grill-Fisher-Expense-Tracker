<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['date', 'category', 'amount', 'vendor', 'payment', 'note'];
    protected $casts = ['date' => 'date'];

    /** Store plain Y-m-d so date filters ("up to today") include today's entries. */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
