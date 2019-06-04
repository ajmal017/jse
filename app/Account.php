<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Account extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'bot_id',
        'exchange_id',
        'api',
        'api_secret',
        'status',
        'is_testnet',
        'memo'
    ];
}
