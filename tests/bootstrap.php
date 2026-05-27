<?php
/**
 * PHPUnit bootstrap for Frame for WooCommerce.
 *
 * Loads the pure helper layer only. WP/WC are NOT bootstrapped — anything
 * that needs them lives in the gateway class, not here.
 */

declare(strict_types=1);

define('FRAME_WC_HELPERS_TESTING', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/class-frame-wc-helpers.php';
