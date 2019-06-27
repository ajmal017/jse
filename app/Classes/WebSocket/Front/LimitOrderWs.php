<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 12:00 PM
 */

namespace App\Classes\WebSocket\Front;


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
                    // Event fire. Sent to Chart.vue

                    dump($jsonMessage);

                    /*if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            //\App\Classes\WebSocket\ConsoleWebSocket::messageParse($jsonMessage, self::$console, self::$candleMaker, self::$chart, self::$priceChannelPeriod, self::$macdSettings);
                        }
                    }*/
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::$console->handle(); // Call the main method of this class
                });

                /* Manual subscription object */
                $requestObject = json_encode(
                        [
                            "op" => "subscribe",
                            "args" => ["instrument:" . self::$symbol] // ["instrument:XBTUSD", "instrument:ETHUSD"]
                        ]
                    );

                $api = "ikeCK-6ZRWtItOkqvqo8F6wO";
                $secret = "JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK";
                //$secret = 'chNOOS4KvNXR_Xq4k4c9qsfoKWvnDecLATCRlcBwyKDYnWgO'; // from bitmex sample


                $verb = 'POST';
                $path = '/api/v1/order';
                $expires = (time() + 86400); // 10 digits
                //$expires = 1518064238;
                //$expires = 1818064238;
                //$data = '{"symbol":"XBTM15","price":219.0,"clOrdID":"mm_bitmex_1a/oemUeQ4CAJZgP3fjHsA","orderQty":98}';
                $data = 'GET/realtime';

                $string = $verb . $path . $expires . $data;

                $signature = hash_hmac('sha256', 'GET/realtime'. $expires, $secret);
                dump($signature);

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
                //$conn->send($requestObject);
                $conn->send($requestObject2);
                $conn->send($requestObject3);

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