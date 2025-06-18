<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningHour extends Model
{
    protected $table = 'opening_hours';

    protected $fillable = [
        'day',
        'open',
        'close',
    ];
}
