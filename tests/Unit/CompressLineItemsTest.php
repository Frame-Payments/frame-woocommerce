<?php

declare(strict_types=1);

namespace FrameWC\Tests\Unit;

use Frame_WC_Helpers;
use PHPUnit\Framework\TestCase;

final class CompressLineItemsTest extends TestCase
{
    public function test_compresses_single_simple_item(): void
    {
        $items = [['product_id' => 61, 'variation_id' => 0, 'quantity' => 9]];
        self::assertSame('61x9', Frame_WC_Helpers::compress_line_items($items));
    }

    public function test_variation_uses_pid_colon_vid_form(): void
    {
        $items = [['product_id' => 61, 'variation_id' => 42, 'quantity' => 2]];
        self::assertSame('61:42x2', Frame_WC_Helpers::compress_line_items($items));
    }

    public function test_joins_multiple_items_with_commas(): void
    {
        $items = [
            ['product_id' => 61, 'variation_id' => 0,  'quantity' => 9],
            ['product_id' => 62, 'variation_id' => 0,  'quantity' => 1],
            ['product_id' => 63, 'variation_id' => 7,  'quantity' => 3],
        ];
        self::assertSame('61x9,62x1,63:7x3', Frame_WC_Helpers::compress_line_items($items));
    }

    public function test_skips_items_without_valid_product_id(): void
    {
        $items = [
            ['product_id' => 0,  'variation_id' => 0, 'quantity' => 1],   // dropped
            ['product_id' => 61, 'variation_id' => 0, 'quantity' => 9],   // kept
            ['variation_id' => 0, 'quantity' => 1],                       // dropped (missing pid)
        ];
        self::assertSame('61x9', Frame_WC_Helpers::compress_line_items($items));
    }

    public function test_returns_empty_string_when_no_items(): void
    {
        self::assertSame('', Frame_WC_Helpers::compress_line_items([]));
    }

    public function test_truncates_long_carts_at_comma_boundary(): void
    {
        // 30 items at "999x99," = 7 chars each = 210 chars total.
        // Must be cut at a comma and stay ≤ 100 chars.
        $items = [];
        for ($i = 0; $i < 30; $i++) {
            $items[] = ['product_id' => 999, 'variation_id' => 0, 'quantity' => 99];
        }
        $result = Frame_WC_Helpers::compress_line_items($items);
        self::assertLessThanOrEqual(100, strlen($result));
        // Must end on a complete segment, not mid-way through "999x9...".
        self::assertMatchesRegularExpression('/^(\d+(?::\d+)?x\d+)(,\d+(?::\d+)?x\d+)*$/', $result);
    }

    public function test_coerces_string_numerics(): void
    {
        $items = [['product_id' => '61', 'variation_id' => '0', 'quantity' => '9']];
        self::assertSame('61x9', Frame_WC_Helpers::compress_line_items($items));
    }
}
