<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class PricechannelSettings extends Model
{
    use Notifiable;
    protected $table = 'pricechannel_settings'; // Custom table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time_frame',
        'sma_filter_period'
    ];
}
