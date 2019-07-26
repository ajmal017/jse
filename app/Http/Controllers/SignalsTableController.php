<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignalsTableController extends Controller
{
    private $table = null;

    public function index($botId){

        $records = DB::table('signal_' . $botId)->get();
        foreach ($records as $record) {
            $this->table[] = [
                'id' => $record->id,
                'date' => $record->date,
                'type' => $record->type,
                'order_id' => $record->order_id,
                'status' => $record->status,
                'order_type' => $record->order_type,
                'direction' => $record->direction,
                'signal_price' => $record->signal_price,
                'signal_volume' => $record->signal_volume,
                'volume' => $record->volume,
                'volume_reminder' => $record->volume_reminder,
                'trade_date' => $record->trade_date,
                'order_price' => $record->order_price,
                'avg_fill_price' => $record->avg_fill_price,
                'trade_commission_percent' => $record->trade_commission_percent,
                'trade_profit' => $record->trade_profit,
                'accumulated_profit' => $record->accumulated_profit,
                'net_profit' => $record->net_profit
            ];
        }
        return ($this->table ? $this->table : null);
    }
}
