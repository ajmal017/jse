<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 11:55 AM
 */

namespace App\Classes\Trading;


class LimitOrder
{
    public function start(){
        /**
         * Websocket connection
         * Ratchet/pawl websocket library
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, ['dns' => '8.8.8.8', 'timeout' => 10]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        /**
         * Subscribe ws and start working with limit orders
         */
        //\App\Classes\WebSocket\Front\BitmexWsListenerFront::subscribe($connector, $loop, $this, $this->argument('botId'));
        \App\Classes\WebSocket\Front\LimitOrderWs::listen($connector, $loop, $this);
    }
}