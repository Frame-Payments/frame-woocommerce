<?php

declare(strict_types=1);

namespace FrameWC\Tests\Unit;

use Frame_WC_Helpers;
use PHPUnit\Framework\TestCase;

final class FitMetadataValueTest extends TestCase
{
    public function test_returns_value_unchanged_when_under_limit(): void
    {
        self::assertSame('short', Frame_WC_Helpers::fit_metadata_value('short'));
    }

    public function test_returns_value_unchanged_at_exact_limit(): void
    {
        $value = str_repeat('a', 100);
        self::assertSame($value, Frame_WC_Helpers::fit_metadata_value($value));
        self::assertSame(100, strlen($value));
    }

    public function test_truncates_long_value_without_commas(): void
    {
        $value  = str_repeat('a', 150);
        $result = Frame_WC_Helpers::fit_metadata_value($value);
        self::assertSame(100, strlen($result));
        self::assertSame(str_repeat('a', 100), $result);
    }

    public function test_truncates_at_last_comma_boundary(): void
    {
        // 110 chars total: "61x9,62x1,…,xx,yyy"  — the last full segment within
        // 100 should survive intact; anything after the cut should be dropped.
        $value  = 'a,bb,cc,' . str_repeat('z', 100); // first comma boundaries are early
        $result = Frame_WC_Helpers::fit_metadata_value($value);
        self::assertLessThanOrEqual(100, strlen($result));
        self::assertSame('a,bb,cc', $result);
    }

    public function test_falls_back_to_hard_cut_when_no_comma_in_first_100_chars(): void
    {
        // 120 chars of letters, then a comma — the comma is past the limit,
        // so the result must just be a hard substring of the first 100.
        $value  = str_repeat('a', 120) . ',tail';
        $result = Frame_WC_Helpers::fit_metadata_value($value);
        self::assertSame(str_repeat('a', 100), $result);
    }

    public function test_handles_empty_string(): void
    {
        self::assertSame('', Frame_WC_Helpers::fit_metadata_value(''));
    }
}
