<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['date', 'category', 'kind', 'amount', 'vendor', 'status', 'note'];

    /** daily = comes off that day's sale · monthly = fixed cost (rent, salary, bills) */
    public const KINDS = ['daily' => 'Daily', 'monthly' => 'Monthly'];
    protected $casts = ['date' => 'date'];

    /** Store plain Y-m-d so date filters ("up to today") include today's entries. */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
