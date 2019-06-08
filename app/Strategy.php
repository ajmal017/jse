<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Strategy extends Model
{
    // protected $table = 'my_flights'; // Custom table name
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'strategy_type_id',
        'execution_symbol_name',
        'is_active',
        'pricechannel_settings_id',
        'macd_settings_id',
        'memo'
    ];
}
