<?php

declare(strict_types=1);

namespace FrameWC\Tests\Unit;

use Frame_WC_Helpers;
use PHPUnit\Framework\TestCase;

final class BuildPhoneFromIndividualTest extends TestCase
{
    public function test_returns_e164_for_bare_dial_code(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => '1',
        ]);
        self::assertSame('+19729003915', $result);
    }

    public function test_returns_e164_for_plus_prefixed_dial_code(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => '+1',
        ]);
        self::assertSame('+19729003915', $result);
    }

    public function test_strips_formatting_from_number_and_country_code(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '(972) 900-3915',
            'country_code' => '+1',
        ]);
        self::assertSame('+19729003915', $result);
    }

    public function test_returns_national_digits_when_country_code_is_iso_alpha2(): void
    {
        // Spec: alpha-2 ISO ("US") can't be turned into a dial code locally, so
        // the function returns national digits and lets downstream code combine
        // with billing_country.
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => 'US',
        ]);
        self::assertSame('9729003915', $result);
    }

    public function test_returns_national_digits_when_country_code_is_empty(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => '',
        ]);
        self::assertSame('9729003915', $result);
    }

    public function test_returns_national_digits_when_country_code_is_missing(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual(['number' => '9729003915']);
        self::assertSame('9729003915', $result);
    }

    public function test_returns_national_digits_when_country_code_has_no_digits(): void
    {
        // "+" alone strips to empty — fall back to national digits.
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => '+',
        ]);
        self::assertSame('9729003915', $result);
    }

    public function test_returns_empty_string_when_number_is_missing(): void
    {
        self::assertSame('', Frame_WC_Helpers::build_phone_from_individual([]));
    }

    public function test_returns_empty_string_when_number_has_no_digits(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => 'abc',
            'country_code' => '1',
        ]);
        self::assertSame('', $result);
    }

    public function test_trims_whitespace_in_country_code(): void
    {
        $result = Frame_WC_Helpers::build_phone_from_individual([
            'number'       => '9729003915',
            'country_code' => '  +1  ',
        ]);
        self::assertSame('+19729003915', $result);
    }
}
