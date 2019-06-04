<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Symbol extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exchange_id',
        'execution_symbol_name',
        'history_symbol_name',
        'commission',
        'memo',
        'is_active'
    ];
}
