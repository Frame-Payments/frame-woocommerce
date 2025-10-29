<?php

namespace Frame\Tests\Unit;

use Frame\Exception;
use Frame\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testExceptionConstructor()
    {
        $message = 'Test error message';
        $code = 500;
        $response = ['error' => 'details'];

        $exception = new Exception($message, $code, null, $response);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($response, $exception->getResponse());
    }

    public function testFromResponseWithErrorStructure()
    {
        $response = [
            'error' => [
                'message' => 'API Error',
                'code' => 400,
            ],
        ];

        $exception = Exception::fromResponse($response);

        $this->assertEquals('API Error', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals($response, $exception->getResponse());
    }

    public function testFromResponseWithMissingErrorFields()
    {
        $response = ['some' => 'data'];

        $exception = Exception::fromResponse($response);

        $this->assertEquals('Unknown error', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals($response, $exception->getResponse());
    }

    public function testGetErrorMessage()
    {
        $errorMessage = '{"errors":[{"detail":"Invalid request"}]}';
        $exception = new Exception($errorMessage);

        $result = Exception::getErrorMessage($exception);

        $this->assertEquals('Invalid request', $result);
    }

    public function testGetErrorMessageWithInvalidJson()
    {
        $errorMessage = 'Not valid JSON';
        $exception = new Exception($errorMessage);

        // This test is designed to ensure that invalid JSON in an error
        // message does not cause a fatal error, but is handled gracefully.
        $result = Exception::getErrorMessage($exception);

        // When JSON is invalid, the original message should be returned.
        $this->assertEquals($errorMessage, $result);
    }
}
