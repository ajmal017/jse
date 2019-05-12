<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 1/8/2019
 * Time: 8:22 AM
 */

namespace App\Classes\WebSocket;

/**
 * Send message to Vue.js via pusher.
 * Message is received in Vue.js and then parsed.
 * Each message has:
 * - clientId (identical to pusher id taken from .env)
 * - type
 * - payload
 *
 * Class PusherApiMessage
 * @package App\Classes\WsApiMessages
 */
class PusherApiMessage
{
    private $clientId;
    private $messageType;
    private $payload;

    // Constructor
    public function __construct()
    {
        //
    }

    public function __get($name)
    {
        if ($name == "clientId") return $this->clientId;
        if ($name == "messageType") return $this->messageType;
        if ($name == "payload") return $this->payload;
    }

    public function __set($name, $value)
    {
        if ($name == "clientId") $this->clientId = $value;
        if ($name == "messageType") $this->messageType = $value;
        if ($name == "payload") $this->payload = $value;
    }

    /**
     * Json serialization.
     * @see https://stackoverflow.com/questions/6836592/serializing-php-object-to-json
     *
     * @return array
     */
    public function toArray()
    {
        $array = get_object_vars($this);
        unset($array['_parent'], $array['_index']);
        array_walk_recursive($array, function (&$property) {
            if (is_object($property) && method_exists($property, 'toArray')) {
                $property = $property->toArray();
            }
        });
        return $array;
    }
}