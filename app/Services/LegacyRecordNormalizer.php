<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

class LegacyRecordNormalizer
{
    public function normalizeLegacyId(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    public function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim((string) $value));
        return $value === '' ? null : $value;
    }

    public function normalizeCatalogName(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }

    public function normalizeTipoPessoa(mixed $value, mixed $document = null): string
    {
        $normalized = mb_strtolower((string) $this->normalizeString($value), 'UTF-8');
        if (in_array($normalized, ['fisica', 'física', 'pf', 'pessoa fisica', 'pessoa física'], true)) {
            return 'fisica';
        }
        if (in_array($normalized, ['juridica', 'jurídica', 'pj', 'pessoa juridica', 'pessoa jurídica'], true)) {
            return 'juridica';
        }

        $digits = $this->normalizeDocument($document);
        return strlen((string) $digits) === 14 ? 'juridica' : 'fisica';
    }

    public function normalizePhone(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            return $digits;
        }

        return $digits;
    }

    public function isValidPhone(?string $digits): bool
    {
        if ($digits === null) {
            return false;
        }

        $length = strlen($digits);
        return $length >= 10 && $length <= 13;
    }

    public function normalizeDocument(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    public function isValidDocument(?string $digits): bool
    {
        if ($digits === null) {
            return false;
        }

        return in_array(strlen($digits), [11, 14], true);
    }

    public function normalizePriority(mixed $value): string
    {
        $normalized = mb_strtolower((string) $this->normalizeString($value), 'UTF-8');

        return match ($normalized) {
            'baixa', 'low' => 'baixa',
            'alta', 'high' => 'alta',
            'urgente', 'urgent' => 'urgente',
            default => 'normal',
        };
    }

    public function normalizeBoolean(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalized = mb_strtolower((string) $this->normalizeString($value), 'UTF-8');
        return in_array($normalized, ['1', 'true', 'sim', 'yes', 'y', 's'], true) ? 1 : 0;
    }

    public function normalizeDateTime(mixed $value, bool $dateOnly = false): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s');
        }

        $string = trim((string) $value);
        if ($string === '' || $string === '0000-00-00' || $string === '0000-00-00 00:00:00') {
            return null;
        }

        $formats = $dateOnly
            ? ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Ymd']
            : ['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $string);
            if ($date instanceof DateTimeImmutable) {
                return $date->format($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s');
            }
        }

        try {
            $date = new DateTimeImmutable($string);
            return $date->format($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    public function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*,\d+$/', $string) === 1) {
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } else {
            $string = str_replace(',', '.', $string);
        }

        return is_numeric($string) ? (float) $string : null;
    }

    public function normalizeSerialLike(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = mb_strtoupper($normalized, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $normalized = $ascii !== false ? $ascii : $normalized;
        $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized);

        return strlen((string) $normalized) >= 6 ? $normalized : null;
    }

    public function normalizeImei(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        $length = strlen($digits);
        return $length >= 14 && $length <= 17 ? $digits : null;
    }
}
