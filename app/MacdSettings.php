<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class MacdSettings extends Model
{
    use Notifiable;
    protected $table = 'macd_settings'; // Custom table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ema_period',
        'macd_line_period',
        'macd_signalline_period'
    ];

}
