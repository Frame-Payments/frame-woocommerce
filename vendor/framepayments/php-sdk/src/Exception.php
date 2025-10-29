<?php

namespace Frame;

class Exception extends \Exception
{
    protected $response;

    public function __construct($message = "", $code = 0, \Exception $previous = null, $response = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public static function fromResponse($response)
    {
        $message = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown error'; // Adjust based on API error structure
        $code = isset($response['error']['code']) ? $response['error']['code'] : 0;

        return new self($message, $code, null, $response);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public static function getErrorMessage(Exception $e)
    {
        $message = $e->getMessage();
        $decoded = json_decode($message, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['errors'][0]['detail'])) {
            return $decoded['errors'][0]['detail'];
        }

        return $message;
    }
}
