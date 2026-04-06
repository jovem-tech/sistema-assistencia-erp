<?php

use App\Services\LegacyRecordNormalizer;
use CodeIgniter\Test\CIUnitTestCase;

final class LegacyRecordNormalizerTest extends CIUnitTestCase
{
    private LegacyRecordNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new LegacyRecordNormalizer();
    }

    public function testNormalizePhoneStripsFormatting(): void
    {
        $this->assertSame('5522999991234', $this->normalizer->normalizePhone('+55 (22) 99999-1234'));
        $this->assertTrue($this->normalizer->isValidPhone('5522999991234'));
    }

    public function testNormalizeDocumentKeepsDigitsOnly(): void
    {
        $this->assertSame('12345678901', $this->normalizer->normalizeDocument('123.456.789-01'));
        $this->assertTrue($this->normalizer->isValidDocument('12345678901'));
        $this->assertFalse($this->normalizer->isValidDocument('1234'));
        $this->assertFalse($this->normalizer->isValidDocument(null));
    }

    public function testNormalizeDateTimeSupportsBrazilianFormat(): void
    {
        $this->assertSame('2026-03-28', $this->normalizer->normalizeDateTime('28/03/2026', true));
        $this->assertSame('2026-03-28 14:30:00', $this->normalizer->normalizeDateTime('28/03/2026 14:30:00'));
    }

    public function testNormalizeDecimalSupportsCommaAndThousands(): void
    {
        $this->assertSame(1234.56, $this->normalizer->normalizeDecimal('1.234,56'));
        $this->assertSame(10.5, $this->normalizer->normalizeDecimal('10,50'));
    }

    public function testNormalizeSerialLikeRemovesFormattingAndKeepsStrongKeysOnly(): void
    {
        $this->assertSame('SNABC12345', $this->normalizer->normalizeSerialLike('SN-ABC 12345'));
        $this->assertNull($this->normalizer->normalizeSerialLike('A1'));
    }

    public function testNormalizeImeiKeepsOnlyValidDigitLengths(): void
    {
        $this->assertSame('356789012345678', $this->normalizer->normalizeImei('35678 901234 5678'));
        $this->assertNull($this->normalizer->normalizeImei('12345'));
    }
}
