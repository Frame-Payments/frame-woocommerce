<?php

namespace Frame\Tests\Unit;

use Frame\Auth;
use Frame\Exception;
use Frame\Tests\TestCase;

class AuthTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the API key after each test
        $reflection = new \ReflectionClass(Auth::class);
        $property = $reflection->getProperty('apiKey');
        $property->setAccessible(true);
        $property->setValue(null, null);

        parent::tearDown();
    }

    public function testSetAndGetApiKey()
    {
        $apiKey = 'test_api_key_123';

        Auth::setApiKey($apiKey);

        $this->assertEquals($apiKey, Auth::getApiKey());
    }

    public function testGetApiKeyThrowsExceptionWhenNotSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API key not set. Use Frame\Auth::setApiKey() to set it.');

        Auth::getApiKey();
    }

    public function testSetApiKeyOverwritesPreviousValue()
    {
        $firstApiKey = 'first_api_key';
        $secondApiKey = 'second_api_key';

        Auth::setApiKey($firstApiKey);
        $this->assertEquals($firstApiKey, Auth::getApiKey());

        Auth::setApiKey($secondApiKey);
        $this->assertEquals($secondApiKey, Auth::getApiKey());
    }
}
