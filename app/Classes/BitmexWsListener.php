<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/17/2019
 * Time: 9:15 PM
 */

namespace App\Classes;

class BitmexWsListener
{
    public static $console;
    public static function subscribe($connector, $loop, $console){

        self::$console = $console;

        /** Pick up the right websocket endpoint accordingly to the exchange */
        $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";
        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    dump($jsonMessage);

                    // Event fire
                    event(new \App\Events\jseevent($jsonMessage));

                    if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            // WebSocketStream::Parse($jsonMessage['data']); // Update quotes, send events to vue
                            // WebSocketStream::stopLossCheck($jsonMessage['data']); // Stop loss execution
                        }
                    }
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::$console->handle(); // Call the main method of this class
                });

                /* Manual subscription object */
                $requestObject = json_encode([
                    "op" => "subscribe",
                    "args" => ["instrument:XBTUSD"] // ["instrument:XBTUSD", "instrument:ETHUSD"]
                ]);
                $conn->send($requestObject);

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