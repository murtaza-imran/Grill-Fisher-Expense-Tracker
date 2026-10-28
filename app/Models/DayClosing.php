<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A day that has been checked and closed (like signing off the paper page). */
class DayClosing extends Model
{
    protected $fillable = ['date', 'closed_at'];
    protected $casts = ['date' => 'date', 'closed_at' => 'datetime'];

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
