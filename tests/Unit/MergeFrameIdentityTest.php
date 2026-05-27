<?php

declare(strict_types=1);

namespace FrameWC\Tests\Unit;

use Frame_WC_Helpers;
use PHPUnit\Framework\TestCase;

final class MergeFrameIdentityTest extends TestCase
{
    public function test_frame_values_win_when_present(): void
    {
        $individual = [
            'email' => 'ricky@example.com',
            'name'  => ['first_name' => 'Ricky', 'last_name' => 'Washy'],
            'phone' => ['number' => '9729003915', 'country_code' => '1'],
        ];
        $result = Frame_WC_Helpers::merge_frame_identity(
            $individual,
            'wc@example.com',
            'WC Default',
            '0000000000'
        );
        self::assertSame('ricky@example.com', $result['email']);
        self::assertSame('Ricky Washy', $result['name']);
        self::assertSame('+19729003915', $result['phone']);
    }

    public function test_defaults_survive_when_individual_is_empty(): void
    {
        $result = Frame_WC_Helpers::merge_frame_identity(
            [],
            'wc@example.com',
            'WC Default',
            '0000000000'
        );
        self::assertSame('wc@example.com', $result['email']);
        self::assertSame('WC Default',     $result['name']);
        self::assertSame('0000000000',     $result['phone']);
    }

    public function test_partial_individual_only_overrides_what_it_provides(): void
    {
        // Only first_name provided; default last name + email + phone survive.
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['name' => ['first_name' => 'Ricky']],
            'wc@example.com',
            'WC Default',
            '0000000000'
        );
        self::assertSame('wc@example.com', $result['email']);
        self::assertSame('Ricky',           $result['name']);
        self::assertSame('0000000000',      $result['phone']);
    }

    public function test_only_last_name_still_overrides_default_name(): void
    {
        // Frame provides last_name only — name becomes "Washy" (trimmed,
        // single token). The default 'WC Default' must NOT survive partial
        // identity collection; that's the regression we just fixed.
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['name' => ['last_name' => 'Washy']],
            'wc@example.com',
            'WC Default',
            ''
        );
        self::assertSame('Washy', $result['name']);
    }

    public function test_email_only_override(): void
    {
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['email' => 'ricky@example.com'],
            'wc@example.com',
            'WC Default',
            ''
        );
        self::assertSame('ricky@example.com', $result['email']);
        self::assertSame('WC Default',         $result['name']);
    }

    public function test_empty_string_email_does_not_override(): void
    {
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['email' => ''],
            'wc@example.com',
            'WC Default',
            ''
        );
        self::assertSame('wc@example.com', $result['email']);
    }

    public function test_phone_with_iso_country_code_returns_national_digits(): void
    {
        // Spec: ISO alpha-2 country_code yields national-only digits.
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['phone' => ['number' => '9729003915', 'country_code' => 'US']],
            '',
            '',
            '0000000000'
        );
        self::assertSame('9729003915', $result['phone']);
    }

    public function test_phone_missing_number_does_not_override_default(): void
    {
        $result = Frame_WC_Helpers::merge_frame_identity(
            ['phone' => ['country_code' => '1']], // no number
            '',
            '',
            '5555555555'
        );
        self::assertSame('5555555555', $result['phone']);
    }

    public function test_camelcase_keys_are_NOT_recognized(): void
    {
        // Regression guard: Frame.js emits snake_case (Accounts API shape).
        // We must NOT silently accept the camelCase keys our older code used
        // — otherwise the bug returns dressed up.
        $individual = [
            'firstName'        => 'Ricky',
            'lastName'         => 'Washy',
            'phoneNumber'      => '9729003915',
            'phoneCountryCode' => '1',
        ];
        $result = Frame_WC_Helpers::merge_frame_identity(
            $individual,
            'wc@example.com',
            'WC Default',
            '0000000000'
        );
        // Nothing in the camelCase payload should have leaked through.
        self::assertSame('WC Default', $result['name']);
        self::assertSame('0000000000', $result['phone']);
    }
}
