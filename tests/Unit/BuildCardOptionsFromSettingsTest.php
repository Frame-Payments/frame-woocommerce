<?php

declare(strict_types=1);

namespace FrameWC\Tests\Unit;

use Frame_WC_Helpers;
use PHPUnit\Framework\TestCase;

final class BuildCardOptionsFromSettingsTest extends TestCase
{
    public function test_defaults_when_settings_array_is_empty(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([]);

        self::assertSame(['preset' => 'clean'], $options['cardTheme']);
        self::assertSame(['number', 'expiry', 'cvc'], $options['fields']);
        self::assertFalse($options['autoFocus']);
        self::assertArrayNotHasKey('identityFields', $options);
    }

    public function test_card_theme_preset_is_passed_through(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings(['card_theme' => 'minimal']);
        self::assertSame(['preset' => 'minimal'], $options['cardTheme']);
    }

    public function test_input_color_and_size_populate_styles(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'style_input_color'     => '#333333',
            'style_input_font_size' => '16px',
        ]);
        self::assertSame(
            ['preset' => 'clean', 'styles' => ['input' => ['color' => '#333333', 'fontSize' => '16px']]],
            $options['cardTheme']
        );
    }

    public function test_blank_styles_are_omitted(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'style_input_color'     => '   ',
            'style_input_font_size' => '',
        ]);
        self::assertSame(['preset' => 'clean'], $options['cardTheme']);
    }

    public function test_auto_focus_is_only_true_when_setting_is_yes(): void
    {
        self::assertTrue(Frame_WC_Helpers::build_card_options_from_settings(['auto_focus' => 'yes'])['autoFocus']);
        self::assertFalse(Frame_WC_Helpers::build_card_options_from_settings(['auto_focus' => 'no'])['autoFocus']);
        self::assertFalse(Frame_WC_Helpers::build_card_options_from_settings(['auto_focus' => ''])['autoFocus']);
        self::assertFalse(Frame_WC_Helpers::build_card_options_from_settings([])['autoFocus']);
    }

    public function test_card_fields_are_always_number_expiry_cvc(): void
    {
        // Regression guard: cardholder `name` is intentionally never emitted.
        // The admin UI doesn't expose the choice; identity collection takes
        // the customer's name. If 'name' starts showing up here again, we've
        // re-introduced the duplicate-prompt UX bug.
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_first_name' => 'required',
            'identity_last_name'  => 'required',
        ]);
        self::assertSame(['number', 'expiry', 'cvc'], $options['fields']);
        self::assertNotContains('name', $options['fields']);
    }

    public function test_identity_field_required_state_maps_to_required_true(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_first_name' => 'required',
            'identity_last_name'  => 'required',
            'identity_email'      => 'required',
        ]);
        self::assertSame(
            [
                'firstName' => ['show' => true, 'required' => true],
                'lastName'  => ['show' => true, 'required' => true],
                'email'     => ['show' => true, 'required' => true],
            ],
            $options['identityFields']
        );
    }

    public function test_identity_field_optional_state_maps_to_required_false(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_first_name' => 'optional',
        ]);
        self::assertSame(
            ['firstName' => ['show' => true, 'required' => false]],
            $options['identityFields']
        );
    }

    public function test_identity_field_hidden_state_omits_field(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_first_name' => 'hidden',
            'identity_last_name'  => 'required',
        ]);
        self::assertArrayHasKey('identityFields', $options);
        self::assertArrayNotHasKey('firstName', $options['identityFields']);
        self::assertArrayHasKey('lastName', $options['identityFields']);
    }

    public function test_phone_setting_expands_to_country_code_and_number(): void
    {
        // 'identity_phone' is a single admin control but Frame.js represents
        // phone as two separate fields. The helper must expand it.
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_phone' => 'required',
        ]);
        self::assertSame(
            [
                'phoneCountryCode' => ['show' => true, 'required' => true],
                'phoneNumber'      => ['show' => true, 'required' => true],
            ],
            $options['identityFields']
        );
    }

    public function test_phone_optional_expands_both_fields_to_optional(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_phone' => 'optional',
        ]);
        self::assertSame(
            [
                'phoneCountryCode' => ['show' => true, 'required' => false],
                'phoneNumber'      => ['show' => true, 'required' => false],
            ],
            $options['identityFields']
        );
    }

    public function test_no_identity_fields_means_no_identity_fields_key(): void
    {
        $options = Frame_WC_Helpers::build_card_options_from_settings([
            'identity_first_name' => 'hidden',
            'identity_last_name'  => 'hidden',
            'identity_email'      => 'hidden',
            'identity_phone'      => 'hidden',
        ]);
        self::assertArrayNotHasKey('identityFields', $options);
    }
}
