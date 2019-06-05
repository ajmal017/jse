<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Bot extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'account_id',
        'symbol_id',
        'time_frame',
        'bars_to_load',
        'volume',
        'front_end_id',
        'rate_limit',
        'status',
        'strategy_id',
        'memo'
    ];
}

