<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Helper;

use App\Helper\TimezoneHelper;
use Carbon\Carbon;
use HyperfTest\TestCase;

class TimezoneHelperTest extends TestCase
{
    public function testTimezoneHelperFunction(): void
    {
        $helper = timezone();
        $this->assertInstanceOf(TimezoneHelper::class, $helper);
    }

    public function testNowReturnsCorrectTimezone(): void
    {
        $now = timezone()->now();
        $this->assertInstanceOf(Carbon::class, $now);
        $this->assertEquals('America/Sao_Paulo', $now->getTimezone()->getName());
    }

    public function testParseReturnsCorrectTimezone(): void
    {
        $date = timezone()->parse('2023-01-01 10:00:00');
        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('America/Sao_Paulo', $date->getTimezone()->getName());
    }

    public function testCreateFromFormatReturnsCorrectTimezone(): void
    {
        $date = timezone()->createFromFormat('Y-m-d H:i', '2023-01-01 10:00');
        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('America/Sao_Paulo', $date->getTimezone()->getName());
    }
}