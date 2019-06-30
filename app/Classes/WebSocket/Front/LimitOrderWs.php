<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 12:00 PM
 */

namespace App\Classes\WebSocket\Front;
use Illuminate\Support\Facades\Cache;


class LimitOrderWs
{
    public static $console;
    public static $symbol;

    public static function listen($connector, $loop, $console){

        self::$console = $console;
        self::$symbol = 'XBTUSD';

        /** Pick up the right websocket endpoint accordingly to the exchange */
        $exchangeWebSocketEndPoint = "wss://testnet.bitmex.com/realtime";
        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    //dump($jsonMessage);

                    /**
                     * Parse all websocket messages.
                     */
                    \App\Classes\WebSocket\Front\LimitOrderMessage::parse($jsonMessage);

                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::$console->handle(); // Call the main method of this class
                });

                $api = "ikeCK-6ZRWtItOkqvqo8F6wO";
                $secret = "JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK";
                $expires = (time() + 86400); // 10 digits
                $signature = hash_hmac('sha256', 'GET/realtime'. $expires, $secret);
                $requestObject2 = json_encode(
                    [
                        "op" => "authKeyExpires",
                        "args" => [$api, $expires, $signature]
                    ]
                );
                $requestObject3 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["order"]
                    ]
                );

                /* Subscribe to order book */
                $requestObject4 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["orderBook10:XBTUSD"]
                    ]
                );

                $conn->send($requestObject2); /* Connection authenticate  */
                $conn->send($requestObject3); /* Subscribe to order channel */
                $conn->send($requestObject4); /* Subscribe to order book with a specific symbol */

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attempt
                //$this->handle(); // Call the main method of this class
                self::subscribe();
                //$loop->stop();
            });
        $loop->run();
    }
}