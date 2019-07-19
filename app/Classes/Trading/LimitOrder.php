<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 11:55 AM
 */

namespace App\Classes\Trading;
use ccxt\bitmex;

class LimitOrder
{
    public function start($console, $botId, $net){
        /**
         * Websocket connection.
         * Ratchet/pawl websocket library.
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, ['dns' => '8.8.8.8', 'timeout' => 10]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        /**
         * Create CCXT instance. JSE-254.
         * It will be used in Exchnage.php. This instance will go through the whole chain of classes, including
         * job dispatch.
         */
        $exchnage = new bitmex();

        /**
         * Subscribe to WS and start working with limit orders
         */
        \App\Classes\WebSocket\Front\LimitOrderWs::listen($connector, $loop, $console, $botId, $net, $exchnage);
    }
}