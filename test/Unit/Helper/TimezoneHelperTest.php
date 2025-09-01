<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Helper;

use App\Helper\TimezoneHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TimezoneHelperTest extends TestCase
{
    private TimezoneHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset Carbon testing state
        Carbon::setTestNow();
        
        $this->helper = new TimezoneHelper();
    }

    protected function tearDown(): void
    {
        // Clean up Carbon testing state
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testConstructorSetsDefaultTimezone(): void
    {
        $helper = new TimezoneHelper();
        
        $this->assertEquals('America/Sao_Paulo', $helper->getTimezone());
    }

    public function testNowReturnsCurrentTimeInCorrectTimezone(): void
    {
        $now = $this->helper->now();
        
        $this->assertInstanceOf(Carbon::class, $now);
        $this->assertEquals('America/Sao_Paulo', $now->getTimezone()->getName());
    }

    public function testParseConvertsStringToCarbon(): void
    {
        $dateString = '2025-01-15 14:30:00';
        $parsed = $this->helper->parse($dateString);
        
        $this->assertInstanceOf(Carbon::class, $parsed);
        $this->assertEquals('America/Sao_Paulo', $parsed->getTimezone()->getName());
        $this->assertEquals('2025-01-15 14:30:00', $parsed->format('Y-m-d H:i:s'));
    }

    public function testParseWithDifferentFormats(): void
    {
        $testCases = [
            '2025-01-15' => '2025-01-15 00:00:00',
            '2025-01-15 14:30' => '2025-01-15 14:30:00',
            '2025-12-31 23:59:59' => '2025-12-31 23:59:59',
        ];

        foreach ($testCases as $input => $expected) {
            $parsed = $this->helper->parse($input);
            $this->assertInstanceOf(Carbon::class, $parsed);
            $this->assertEquals('America/Sao_Paulo', $parsed->getTimezone()->getName());
        }
    }

    public function testCreateFromFormatWithValidFormat(): void
    {
        $format = 'Y-m-d H:i';
        $datetime = '2025-01-15 14:30';
        
        $carbon = $this->helper->createFromFormat($format, $datetime);
        
        $this->assertInstanceOf(Carbon::class, $carbon);
        $this->assertEquals('America/Sao_Paulo', $carbon->getTimezone()->getName());
        $this->assertEquals('2025-01-15 14:30:00', $carbon->format('Y-m-d H:i:s'));
    }

    public function testCreateFromFormatWithInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->helper->createFromFormat('Y-m-d H:i', 'invalid-date');
    }

    public function testCreateFromFormatWithDifferentFormats(): void
    {
        $testCases = [
            ['d/m/Y', '15/01/2025', '2025-01-15'],
            ['Y-m-d H:i:s', '2025-01-15 14:30:45', '2025-01-15 14:30:45'],
            ['d-m-Y H:i', '15-01-2025 14:30', '2025-01-15 14:30:00'],
        ];

        foreach ($testCases as [$format, $input, $expectedDate]) {
            $carbon = $this->helper->createFromFormat($format, $input);
            $this->assertInstanceOf(Carbon::class, $carbon);
            $this->assertEquals('America/Sao_Paulo', $carbon->getTimezone()->getName());
        }
    }

    public function testGetTimezone(): void
    {
        $timezone = $this->helper->getTimezone();
        
        $this->assertIsString($timezone);
        $this->assertEquals('America/Sao_Paulo', $timezone);
    }

    public function testIsInPastWithPastDate(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $pastDate = Carbon::create(2025, 1, 14, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertTrue($this->helper->isInPast($pastDate));
    }

    public function testIsInPastWithFutureDate(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $futureDate = Carbon::create(2025, 1, 16, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertFalse($this->helper->isInPast($futureDate));
    }

    public function testIsInPastWithCurrentTime(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $sameTime = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertFalse($this->helper->isInPast($sameTime));
    }

    public function testIsInFutureWithFutureDate(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $futureDate = Carbon::create(2025, 1, 16, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertTrue($this->helper->isInFuture($futureDate));
    }

    public function testIsInFutureWithPastDate(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $pastDate = Carbon::create(2025, 1, 14, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertFalse($this->helper->isInFuture($pastDate));
    }

    public function testIsInFutureWithCurrentTime(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $sameTime = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        
        $this->assertFalse($this->helper->isInFuture($sameTime));
    }

    public function testTimezoneConsistency(): void
    {
        $now = $this->helper->now();
        $parsed = $this->helper->parse('2025-01-15 14:30');
        $formatted = $this->helper->createFromFormat('Y-m-d H:i', '2025-01-15 14:30');
        
        $this->assertEquals($now->getTimezone(), $parsed->getTimezone());
        $this->assertEquals($now->getTimezone(), $formatted->getTimezone());
    }

    public function testDateComparisons(): void
    {
        // Set a fixed "now" for testing
        $fixedNow = Carbon::create(2025, 1, 15, 12, 0, 0, 'America/Sao_Paulo');
        Carbon::setTestNow($fixedNow);
        
        $helper = new TimezoneHelper();

        $past = Carbon::now('America/Sao_Paulo')->subHour();
        $future = Carbon::now('America/Sao_Paulo')->addHour();
        $same = Carbon::now('America/Sao_Paulo');

        $this->assertTrue($helper->isInPast($past));
        $this->assertFalse($helper->isInPast($future));
        $this->assertTrue($helper->isInPast($same));
        
        $this->assertFalse($helper->isInFuture($past));
        $this->assertTrue($helper->isInFuture($future));
        $this->assertFalse($helper->isInFuture($same));
    }

    public function testConstructorResetsTestTime(): void
    {
        // Set test time
        $testTime = Carbon::create(2025, 1, 1, 12, 0, 0);
        Carbon::setTestNow($testTime);
        
        // Create new helper - should reset test time
        $helper = new TimezoneHelper();
        
        // Test time should be reset
        $this->assertNull(Carbon::getTestNow());
    }

    public function testPhpTimezoneIsSet(): void
    {
        $helper = new TimezoneHelper();
        
        // Check that PHP timezone was set
        $phpTimezone = date_default_timezone_get();
        $this->assertEquals('America/Sao_Paulo', $phpTimezone);
    }

    /**
     * Test edge cases and error conditions
     */
    public function testParseWithInvalidDate(): void
    {
        // Carbon will throw an exception for completely invalid dates
        $this->expectException(\InvalidArgumentException::class);
        $this->helper->parse('invalid-date-format');
    }

    public function testCreateFromFormatEdgeCases(): void
    {
        // Test leap year
        $leapYear = $this->helper->createFromFormat('Y-m-d', '2024-02-29');
        $this->assertEquals('2024-02-29', $leapYear->format('Y-m-d'));
        
        // Test end of year
        $endOfYear = $this->helper->createFromFormat('Y-m-d H:i:s', '2025-12-31 23:59:59');
        $this->assertEquals('2025-12-31 23:59:59', $endOfYear->format('Y-m-d H:i:s'));
    }

    public function testMultipleInstances(): void
    {
        $helper1 = new TimezoneHelper();
        $helper2 = new TimezoneHelper();
        
        $this->assertEquals($helper1->getTimezone(), $helper2->getTimezone());
        
        $time1 = $helper1->now();
        $time2 = $helper2->now();
        
        // Times should be very close (within a few seconds)
        $this->assertLessThan(5, abs($time1->timestamp - $time2->timestamp));
    }
}