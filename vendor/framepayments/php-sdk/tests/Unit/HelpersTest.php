<?php

namespace Frame\Tests\Unit;

use Frame\Helpers;
use Frame\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function testConvertAlpha2ToAlpha3()
    {
        $result = Helpers::convertAlpha2ToAlpha3('US');
        $this->assertEquals('USA', $result);
    }

    public function testConvertAlpha2ToAlpha3WithGB()
    {
        $result = Helpers::convertAlpha2ToAlpha3('GB');
        $this->assertEquals('GBR', $result);
    }

    public function testConvertAlpha2ToAlpha3WithCA()
    {
        $result = Helpers::convertAlpha2ToAlpha3('CA');
        $this->assertEquals('CAN', $result);
    }

    public function testConvertAlpha2ToAlpha3WithDE()
    {
        $result = Helpers::convertAlpha2ToAlpha3('DE');
        $this->assertEquals('DEU', $result);
    }

    public function testConvertAlpha2ToAlpha3WithFR()
    {
        $result = Helpers::convertAlpha2ToAlpha3('FR');
        $this->assertEquals('FRA', $result);
    }

    public function testConvertAlpha2ToAlpha3WithInvalidCode()
    {
        $this->expectException(\League\ISO3166\Exception\OutOfBoundsException::class);
        Helpers::convertAlpha2ToAlpha3('XX');
    }

    public function testConvertAlpha2ToAlpha3WithEmptyString()
    {
        $this->expectException(\League\ISO3166\Exception\DomainException::class);
        Helpers::convertAlpha2ToAlpha3('');
    }
}
