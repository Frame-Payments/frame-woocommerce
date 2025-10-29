<?php

namespace Frame;

class Auth
{
    private static $apiKey;

    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    public static function getApiKey()
    {
        if (! self::$apiKey) {
            throw new Exception('API key not set. Use Frame\Auth::setApiKey() to set it.'); // Updated class name if necessary
        }

        return self::$apiKey;
    }
}
