<?php

declare(strict_types=1);

namespace App\Gs1;

final class DigitalLinkParser
{
    /** @var array<string, string> */
    private array $ais = [];

    /**
     * @param array<string, string> $ais
     */
    private function __construct(array $ais)
    {
        $this->ais = $ais;
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromRequest(string $requestUri, array $query): self
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $ais = self::parsePath($path);

        foreach ($query as $key => $value) {
            $key = (string) $key;
            if ($key === '' || !is_string($value)) {
                continue;
            }
            if (!preg_match('/^\d{2,4}$/', $key)) {
                continue;
            }
            $ais[(string) $key] = $value;
        }

        return new self($ais);
    }

    /**
     * @return array<string, string>
     */
    private static function parsePath(string $path): array
    {
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));
        $ais = [];

        for ($i = 0; $i + 1 < count($segments); $i += 2) {
            $ai = (string) $segments[$i];
            $value = rawurldecode($segments[$i + 1]);
            if (!preg_match('/^\d{2,4}$/', $ai)) {
                continue;
            }
            $ais[$ai] = $value;
        }

        return $ais;
    }

    public function isEmpty(): bool
    {
        return $this->ais === [];
    }

    private function aiValue(string $ai): ?string
    {
        if (array_key_exists($ai, $this->ais)) {
            return (string) $this->ais[$ai];
        }
        $asInt = (int) $ai;
        if ((string) $asInt === $ai && array_key_exists($asInt, $this->ais)) {
            return (string) $this->ais[$asInt];
        }
        return null;
    }

    public function hasGtin(): bool
    {
        return $this->gtin() !== null;
    }

    public function gtin(): ?string
    {
        $gtinRaw = $this->aiValue('01');
        if ($gtinRaw === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $gtinRaw);
        if (!is_string($digits)) {
            $digits = '';
        }
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) < 8 || strlen($digits) > 14) {
            return null;
        }

        return str_pad($digits, 14, '0', STR_PAD_LEFT);
    }

    /**
     * Peso líquido em gramas (AI 310n, kg).
     */
    public function netWeightGrams(): ?float
    {
        return $this->measureToBase('310', 1000.0);
    }

    /**
     * Volume líquido em ml (AI 315n, litros).
     */
    public function netVolumeMl(): ?float
    {
        return $this->measureToBase('315', 1000.0);
    }

    private function measureToBase(string $aiPrefix, float $factor): ?float
    {
        foreach ($this->ais as $ai => $raw) {
            $ai = (string) $ai;
            if (!preg_match('/^' . $aiPrefix . '(\d)$/', $ai, $m)) {
                continue;
            }
            $decimals = (int) $m[1];
            $digits = preg_replace('/\D/', '', (string) $raw);
            if (!is_string($digits) || $digits === '') {
                continue;
            }
            $value = ((int) $digits) / pow(10, $decimals);
            return $value * $factor;
        }
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function raw(): array
    {
        return $this->ais;
    }

    /**
     * @return list<array{ai: string, label: string, raw: string, display: string, type: string}>
     */
    public function displayRows(): array
    {
        $rows = [];
        $preferred = ['01', '10', '21', '11', '17', '15', '13', '3103', '3922'];
        $keys = array_map('strval', array_keys($this->ais));
        usort($keys, static function (string $a, string $b) use ($preferred): int {
            $ia = array_search($a, $preferred, true);
            $ib = array_search($b, $preferred, true);
            $ia = $ia === false ? 1000 : $ia;
            $ib = $ib === false ? 1000 : $ib;
            if ($ia === $ib) {
                return strcmp($a, $b);
            }
            return $ia <=> $ib;
        });

        foreach ($keys as $ai) {
            $ai = (string) $ai;
            $meta = ApplicationIdentifiers::meta($ai);
            $raw = $this->aiValue($ai);
            if ($raw === null) {
                continue;
            }
            $rows[] = [
                'ai' => $ai,
                'label' => $meta['label'],
                'raw' => $raw,
                'display' => self::formatValue($meta['type'], $raw),
                'type' => $meta['type'],
            ];
        }

        return $rows;
    }

    public function expiryStatus(): ?string
    {
        $expiryRaw = $this->aiValue('17');
        if ($expiryRaw === null) {
            return null;
        }

        $date = self::parseYyMmDd($expiryRaw);
        if ($date === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        return $date < $today ? 'expired' : 'valid';
    }

    private static function formatValue(string $type, string $raw): string
    {
        if ($type === 'date') {
            $date = self::parseYyMmDd($raw);
            return $date ? $date->format('d/m/Y') : $raw;
        }

        if ($type === 'gtin') {
            $digits = preg_replace('/\D/', '', $raw);
            if (!is_string($digits)) {
                $digits = $raw;
            }
            return str_pad($digits, 14, '0', STR_PAD_LEFT);
        }

        if (strpos($type, 'decimal:') === 0) {
            $parts = explode(':', $type);
            $decimals = isset($parts[1]) ? (int) $parts[1] : 0;
            $unit = isset($parts[2]) ? $parts[2] : '';
            return self::formatDecimal($raw, $decimals) . ' ' . $unit;
        }

        if (strpos($type, 'amount:') === 0) {
            $decimals = (int) substr($type, strlen('amount:'));
            $currency = getenv('APP_CURRENCY') ?: 'BRL';
            return self::formatMoney($raw, $decimals, $currency);
        }

        if (strpos($type, 'amount_iso:') === 0) {
            $decimals = (int) substr($type, strlen('amount_iso:'));
            $iso = substr($raw, 0, 3);
            $amount = substr($raw, 3);
            return self::formatMoney($amount, $decimals, $iso !== '' ? $iso : 'BRL');
        }

        return $raw;
    }

    private static function formatDecimal(string $raw, int $decimals): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            $digits = '0';
        }
        $int = (int) $digits;
        $value = $decimals > 0 ? $int / pow(10, $decimals) : $int;
        return number_format($value, $decimals, ',', '.');
    }

    private static function formatMoney(string $raw, int $decimals, string $currency): string
    {
        $formatted = self::formatDecimal($raw, $decimals);
        $code = strtoupper($currency);
        if ($code === 'BRL' || $code === '986') {
            return 'R$ ' . $formatted;
        }
        if ($code === 'USD' || $code === '840') {
            return 'US$ ' . $formatted;
        }
        if ($code === 'EUR' || $code === '978') {
            return '€ ' . $formatted;
        }
        return $formatted . ' ' . $currency;
    }

    private static function parseYyMmDd(string $raw): ?\DateTimeImmutable
    {
        if (!preg_match('/^(\d{2})(\d{2})(\d{2})$/', $raw, $m)) {
            return null;
        }

        $year = (int) $m[1];
        $year += $year >= 50 ? 1900 : 2000;
        $month = (int) $m[2];
        $day = (int) $m[3];

        if (!checkdate($month, $day === 0 ? 1 : $day, $year)) {
            return null;
        }

        if ($day === 0) {
            $date = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
            return $date->modify('last day of this month');
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day)) ?: null;
    }
}
