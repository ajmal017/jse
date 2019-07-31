<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Check the state of workers.
 *
 * Class WorkerStatusController
 * @package App\Http\Controllers
 */
class WorkerStatusController extends Controller
{
    private $response;
    public function get($botId){
        $row = DB::table('bots')
            ->where('id',$botId)
            ->get()[0];

        if (time() - $row->front_worker_update_time > 10){
            $this->response = 'Front worker is offline<br>';
        }
        else {
            $this->response = 'Front worker is online<br>';
        }

        if (time() - $row->execution_worker_update_time > 10){
            $this->response .= 'Execution worker is offline<br>';
        } else {
            $this->response .= 'Execution worker is online<br>';
        }

        if (time() - $row->que_worker_update_time > 10){
            $this->response .= 'Que worker is offline<br>';
        } else {
            $this->response .= 'Que worker is online<br>';
        }

        return($this->response);
    }
}
