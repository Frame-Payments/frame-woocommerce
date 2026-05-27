<?php
/**
 * Pure helpers for Frame for WooCommerce.
 *
 * Everything here is framework-free (no WP, no WC, no SDK) so it can be
 * exercised by plain PHPUnit. Anything that needs WP/WC lives in the gateway
 * class and delegates the data-shaping work here.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'FRAME_WC_HELPERS_TESTING' ) ) {
    exit;
}

class Frame_WC_Helpers {

    /** Frame's per-value metadata cap. */
    const METADATA_VALUE_LIMIT = 100;

    /**
     * Truncate a metadata value to fit Frame's per-value char limit.
     * If the input contains commas and would exceed the limit, the cut
     * happens at the last comma boundary so we don't leave a half-encoded
     * item; otherwise it's a flat substring.
     */
    public static function fit_metadata_value( string $value ): string {
        if ( strlen( $value ) <= self::METADATA_VALUE_LIMIT ) {
            return $value;
        }
        $truncated  = substr( $value, 0, self::METADATA_VALUE_LIMIT );
        $last_comma = strrpos( $truncated, ',' );
        return $last_comma !== false ? substr( $truncated, 0, $last_comma ) : $truncated;
    }

    /**
     * Compress an array of line-item descriptors into Frame's metadata format:
     * "<pid>x<qty>" (or "<pid>:<vid>x<qty>" when variation_id > 0), joined by
     * commas, then trimmed to fit METADATA_VALUE_LIMIT at a comma boundary.
     *
     * Each item should be an associative array: ['product_id', 'variation_id', 'quantity'].
     * Items with missing/non-positive product_id are skipped.
     */
    public static function compress_line_items( array $items ): string {
        $parts = [];
        foreach ( $items as $item ) {
            $pid = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
            if ( $pid <= 0 ) {
                continue;
            }
            $vid   = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;
            $qty   = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
            $parts[] = $vid > 0 ? "{$pid}:{$vid}x{$qty}" : "{$pid}x{$qty}";
        }
        return self::fit_metadata_value( implode( ',', $parts ) );
    }

    /**
     * Build an E.164-ish phone string from Frame's { number, country_code }.
     *
     *  - ISO alpha-2 country code (e.g. "US"): no dial-code lookup is done
     *    server-side, so the national digits are returned alone.
     *  - Bare dial code ("1") or "+"-prefixed ("+1"): prepended to the digits.
     *  - Missing/empty country_code: national digits only.
     *
     * Returns '' when no usable number is present.
     */
    public static function build_phone_from_individual( array $phone ): string {
        $raw    = isset( $phone['number'] ) ? (string) $phone['number'] : '';
        $digits = preg_replace( '/[^0-9]/', '', $raw );
        if ( $digits === '' || $digits === null ) {
            return '';
        }

        $cc = isset( $phone['country_code'] ) ? trim( (string) $phone['country_code'] ) : '';
        if ( $cc === '' ) {
            return $digits;
        }
        if ( preg_match( '/^[A-Za-z]{2}$/', $cc ) ) {
            return $digits;
        }
        $cc_digits = preg_replace( '/[^0-9]/', '', $cc );
        if ( $cc_digits === '' || $cc_digits === null ) {
            return $digits;
        }
        return '+' . $cc_digits . $digits;
    }

    /**
     * Merge a Frame.js `individual` object (Accounts-API shape) into a base
     * set of WC-derived identity fields. Frame-collected values win when
     * present; otherwise the WC defaults pass through unchanged.
     *
     * The Frame individual shape (per frame-js/lib/identity.ts):
     *   { email?, name?: { first_name?, last_name? }, phone?: { number?, country_code? } }
     *
     * @param array  $individual    Raw 'individual' payload from Frame.js (may be empty).
     * @param string $default_email Falls through when Frame didn't collect email.
     * @param string $default_name  Falls through when Frame didn't collect first/last.
     * @param string $default_phone Falls through when Frame didn't collect phone.
     * @return array{email:string,name:string,phone:string}
     */
    public static function merge_frame_identity(
        array $individual,
        string $default_email,
        string $default_name,
        string $default_phone
    ): array {
        $email = $default_email;
        $name  = $default_name;
        $phone = $default_phone;

        $ind_name  = isset( $individual['name'] )  && is_array( $individual['name'] )  ? $individual['name']  : [];
        $ind_phone = isset( $individual['phone'] ) && is_array( $individual['phone'] ) ? $individual['phone'] : [];

        $first = isset( $ind_name['first_name'] ) ? (string) $ind_name['first_name'] : '';
        $last  = isset( $ind_name['last_name'] )  ? (string) $ind_name['last_name']  : '';
        if ( $first !== '' || $last !== '' ) {
            $name = trim( $first . ' ' . $last );
        }

        if ( ! empty( $individual['email'] ) ) {
            $email = (string) $individual['email'];
        }

        $frame_phone = self::build_phone_from_individual( $ind_phone );
        if ( $frame_phone !== '' ) {
            $phone = $frame_phone;
        }

        return [
            'email' => $email,
            'name'  => $name,
            'phone' => $phone,
        ];
    }

    /**
     * Build the options array passed to frame.createElement('card', ...) from
     * a flat settings array (the gateway's persisted options).
     *
     * Expected keys: 'card_theme', 'auto_focus', 'style_input_color',
     * 'style_input_font_size', 'identity_first_name', 'identity_last_name',
     * 'identity_email', 'identity_phone' — each as stored by WC_Settings_API.
     *
     * Returns the canonical Frame.js options shape (cardTheme, fields,
     * autoFocus, identityFields). The cardTheme is emitted as plain data
     * { preset, styles } and reconstructed on the JS side.
     */
    public static function build_card_options_from_settings( array $settings ): array {
        $theme_preset = isset( $settings['card_theme'] ) && $settings['card_theme'] !== ''
            ? (string) $settings['card_theme']
            : 'clean';

        $card_theme = [ 'preset' => $theme_preset ];

        $input_styles = [];
        $color        = isset( $settings['style_input_color'] ) ? trim( (string) $settings['style_input_color'] ) : '';
        $size         = isset( $settings['style_input_font_size'] ) ? trim( (string) $settings['style_input_font_size'] ) : '';
        if ( $color !== '' ) {
            $input_styles['color'] = $color;
        }
        if ( $size !== '' ) {
            $input_styles['fontSize'] = $size;
        }
        if ( ! empty( $input_styles ) ) {
            $card_theme['styles'] = [ 'input' => $input_styles ];
        }

        $options = [
            'cardTheme' => $card_theme,
            'fields'    => [ 'number', 'expiry', 'cvc' ],
            'autoFocus' => isset( $settings['auto_focus'] ) && $settings['auto_focus'] === 'yes',
        ];

        $identity_fields = [];
        $field_map       = [
            'identity_first_name' => 'firstName',
            'identity_last_name'  => 'lastName',
            'identity_email'      => 'email',
            'identity_phone'      => 'phone',
        ];
        foreach ( $field_map as $option_key => $frame_key ) {
            $state = isset( $settings[ $option_key ] ) ? (string) $settings[ $option_key ] : 'hidden';
            if ( $state === 'hidden' ) {
                continue;
            }
            $required = ( $state === 'required' );
            if ( $frame_key === 'phone' ) {
                $identity_fields['phoneCountryCode'] = [ 'show' => true, 'required' => $required ];
                $identity_fields['phoneNumber']      = [ 'show' => true, 'required' => $required ];
            } else {
                $identity_fields[ $frame_key ] = [ 'show' => true, 'required' => $required ];
            }
        }
        if ( ! empty( $identity_fields ) ) {
            $options['identityFields'] = $identity_fields;
        }

        return $options;
    }
}
