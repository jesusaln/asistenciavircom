<?php

namespace App\Support;

/**
 * Reduce PII en arreglos pasados a Log::info / error (teléfonos, cuerpos de mensaje, etc.).
 */
final class SensitiveDataLog
{
    public const DEPTH_LIMIT = 10;

    public static function maskPhone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) < 4) {
            return '****';
        }

        return '…' . substr($digits, -4);
    }

    /**
     * @return array|mixed
     */
    public static function redact(mixed $data, int $depth = 0): mixed
    {
        if ($depth > self::DEPTH_LIMIT) {
            return '[redacted:depth]';
        }

        if (is_string($data)) {
            if (strlen($data) > 64) {
                return substr($data, 0, 3) . '…[redacted len=' . strlen($data) . ']';
            }

            return '…';
        }

        if (! is_array($data)) {
            return $data;
        }

        $sensitiveKeys = [
            'to', 'from', 'formatted_to', 'phone', 'telefono', 'recipient', 'payer',
        ];

        $out = [];
        foreach ($data as $k => $v) {
            $key = is_string($k) ? strtolower($k) : (string) $k;
            if ($key === 'text' && is_string($v)) {
                $out[$k] = self::maskMessageBody($v);
                continue;
            }
            if (in_array($key, $sensitiveKeys, true)) {
                $out[$k] = is_string($v) ? self::maskPhone($v) : self::redact($v, $depth + 1);
                continue;
            }
            if (in_array($key, ['payload', 'user_data', 'custom_data', 'data', 'template', 'components', 'text'], true)) {
                $out[$k] = self::redact($v, $depth + 1);
                continue;
            }
            if ($key === 'body' && is_string($v)) {
                $out[$k] = self::maskMessageBody($v);
                continue;
            }
            if ($key === 'error_response' || $key === 'response') {
                $out[$k] = self::redact($v, $depth + 1);
                continue;
            }
            if ($key === 'trace' && is_string($v)) {
                $out[$k] = strlen($v) > 64
                    ? substr($v, 0, 3) . '…[redacted len=' . strlen($v) . ']'
                    : '…';
                continue;
            }
            if (is_string($v) && strlen($v) > 256) {
                $out[$k] = substr($v, 0, 3) . '…[redacted len=' . strlen($v) . ']';
                continue;
            }
            if (is_array($v)) {
                $out[$k] = self::redact($v, $depth + 1);
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    public static function maskMessageBody(string $message): string
    {
        if (strlen($message) <= 12) {
            return '****';
        }

        return substr($message, 0, 2) . '…[len=' . strlen($message) . ']';
    }
}
