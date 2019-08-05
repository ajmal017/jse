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
    private $response = array();
    public function get($botId){
        $row = DB::table('bots')
            ->where('id',$botId)
            ->get()[0];

        if (time() - $row->front_worker_update_time > 10){
            $this->response['isFrontWorkerRunning'] = false;
        }
        else {
            $this->response['isFrontWorkerRunning'] = true;
        }

        if (time() - $row->execution_worker_update_time > 10){
            $this->response['isExecutionWorkerRunning'] = false;
        } else {
            $this->response['isExecutionWorkerRunning'] = true;
        }

        if (time() - $row->que_worker_update_time > 10){
            $this->response['isQueWorkerRunning'] = false;
        } else {
            $this->response['isQueWorkerRunning'] = true;
        }

        return($this->response);
    }
}
