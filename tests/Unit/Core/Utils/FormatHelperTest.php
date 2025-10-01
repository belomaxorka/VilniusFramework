<?php declare(strict_types=1);

use Core\Utils\FormatHelper;

describe('FormatHelper::formatBytes', function () {
    test('formats zero bytes', function () {
        expect(FormatHelper::formatBytes(0))->toBe('0 B');
    });

    test('formats bytes', function () {
        expect(FormatHelper::formatBytes(512))->toBe('512.00 B');
    });

    test('formats kilobytes', function () {
        expect(FormatHelper::formatBytes(1024))->toBe('1.00 KB');
        expect(FormatHelper::formatBytes(1536))->toBe('1.50 KB');
    });

    test('formats megabytes', function () {
        expect(FormatHelper::formatBytes(1048576))->toBe('1.00 MB');
        expect(FormatHelper::formatBytes(5242880))->toBe('5.00 MB');
    });

    test('formats gigabytes', function () {
        expect(FormatHelper::formatBytes(1073741824))->toBe('1.00 GB');
        expect(FormatHelper::formatBytes(2147483648))->toBe('2.00 GB');
    });

    test('formats terabytes', function () {
        expect(FormatHelper::formatBytes(1099511627776))->toBe('1.00 TB');
    });

    test('formats petabytes', function () {
        expect(FormatHelper::formatBytes(1125899906842624))->toBe('1.00 PB');
    });

    test('handles negative values', function () {
        expect(FormatHelper::formatBytes(-1024))->toBe('1.00 KB');
        expect(FormatHelper::formatBytes(-1048576))->toBe('1.00 MB');
    });

    test('custom precision', function () {
        expect(FormatHelper::formatBytes(1536, 0))->toBe('2 KB');
        expect(FormatHelper::formatBytes(1536, 1))->toBe('1.5 KB');
        expect(FormatHelper::formatBytes(1536, 3))->toBe('1.500 KB');
    });

    test('handles large values correctly', function () {
        $bytes = 5368709120; // 5GB
        $result = FormatHelper::formatBytes($bytes);
        expect($result)->toContain('GB');
    });
});

describe('FormatHelper::formatTime', function () {
    test('formats microseconds', function () {
        expect(FormatHelper::formatTime(0.1))->toBe('100.00 μs');
        expect(FormatHelper::formatTime(0.5))->toBe('500.00 μs');
        expect(FormatHelper::formatTime(0.999))->toBe('999.00 μs');
    });

    test('formats milliseconds', function () {
        expect(FormatHelper::formatTime(1))->toBe('1.00 ms');
        expect(FormatHelper::formatTime(15.5))->toBe('15.50 ms');
        expect(FormatHelper::formatTime(999))->toBe('999.00 ms');
    });

    test('formats seconds', function () {
        expect(FormatHelper::formatTime(1000))->toBe('1.00 s');
        expect(FormatHelper::formatTime(1500))->toBe('1.50 s');
        expect(FormatHelper::formatTime(5000))->toBe('5.00 s');
    });

    test('custom precision', function () {
        expect(FormatHelper::formatTime(15.567, 0))->toBe('16 ms');
        expect(FormatHelper::formatTime(15.567, 1))->toBe('15.6 ms');
        expect(FormatHelper::formatTime(15.567, 3))->toBe('15.567 ms');
    });

    test('handles edge cases', function () {
        expect(FormatHelper::formatTime(0))->toBe('0.00 μs');
        expect(FormatHelper::formatTime(0.001))->toBe('1.00 μs');
    });
});

describe('FormatHelper::formatNumber', function () {
    test('formats integers', function () {
        expect(FormatHelper::formatNumber(1234567))->toBe('1,234,567');
        expect(FormatHelper::formatNumber(1000000))->toBe('1,000,000');
    });

    test('formats floats with decimals', function () {
        expect(FormatHelper::formatNumber(1234.5678, 2))->toBe('1,234.57');
        expect(FormatHelper::formatNumber(9999.999, 1))->toBe('10,000.0');
    });

    test('handles zero', function () {
        expect(FormatHelper::formatNumber(0))->toBe('0');
    });

    test('handles negative numbers', function () {
        expect(FormatHelper::formatNumber(-1234567))->toBe('-1,234,567');
    });
});

describe('FormatHelper::formatPercent', function () {
    test('formats percentage', function () {
        expect(FormatHelper::formatPercent(75.5))->toBe('75.50%');
        expect(FormatHelper::formatPercent(100))->toBe('100.00%');
        expect(FormatHelper::formatPercent(0))->toBe('0.00%');
    });

    test('custom precision', function () {
        expect(FormatHelper::formatPercent(75.567, 0))->toBe('76%');
        expect(FormatHelper::formatPercent(75.567, 1))->toBe('75.6%');
        expect(FormatHelper::formatPercent(75.567, 3))->toBe('75.567%');
    });

    test('handles edge cases', function () {
        expect(FormatHelper::formatPercent(0.01))->toBe('0.01%');
        expect(FormatHelper::formatPercent(99.99))->toBe('99.99%');
    });
});

describe('FormatHelper::getColorByThreshold', function () {
    test('returns green for low values', function () {
        $color = FormatHelper::getColorByThreshold(30, 50, 75);
        expect($color)->toBe('#66bb6a');
    });

    test('returns orange for warning values', function () {
        $color = FormatHelper::getColorByThreshold(60, 50, 75);
        expect($color)->toBe('#ffa726');
    });

    test('returns red for critical values', function () {
        $color = FormatHelper::getColorByThreshold(80, 50, 75);
        expect($color)->toBe('#ef5350');
    });

    test('handles boundary values correctly', function () {
        // Exactly at warning threshold
        expect(FormatHelper::getColorByThreshold(50, 50, 75))->toBe('#ffa726');
        
        // Exactly at critical threshold
        expect(FormatHelper::getColorByThreshold(75, 50, 75))->toBe('#ef5350');
        
        // Just below warning
        expect(FormatHelper::getColorByThreshold(49.99, 50, 75))->toBe('#66bb6a');
    });

    test('handles zero values', function () {
        expect(FormatHelper::getColorByThreshold(0, 50, 75))->toBe('#66bb6a');
    });

    test('handles values above 100', function () {
        expect(FormatHelper::getColorByThreshold(150, 50, 75))->toBe('#ef5350');
    });
});

describe('FormatHelper Integration', function () {
    test('all formatters work together', function () {
        $bytes = 1536;
        $time = 15.5;
        $number = 1234567;
        $percent = 75.5;
        
        expect(FormatHelper::formatBytes($bytes))->toBe('1.50 KB');
        expect(FormatHelper::formatTime($time))->toBe('15.50 ms');
        expect(FormatHelper::formatNumber($number))->toBe('1,234,567');
        expect(FormatHelper::formatPercent($percent))->toBe('75.50%');
    });

    test('can be used in real-world scenario', function () {
        // Симуляция реального использования
        $memoryUsed = 8388608; // 8MB
        $executionTime = 123.45; // 123.45ms
        $itemsProcessed = 1000000;
        $successRate = 98.76;
        
        $formattedMemory = FormatHelper::formatBytes($memoryUsed);
        $formattedTime = FormatHelper::formatTime($executionTime);
        $formattedItems = FormatHelper::formatNumber($itemsProcessed);
        $formattedRate = FormatHelper::formatPercent($successRate);
        
        expect($formattedMemory)->toBe('8.00 MB');
        expect($formattedTime)->toBe('123.45 ms');
        expect($formattedItems)->toBe('1,000,000');
        expect($formattedRate)->toBe('98.76%');
    });
});

