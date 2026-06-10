<?php

namespace App\Helpers;

class Utf8Helper
{
    /**
     * Limpia una cadena de caracteres UTF-8 inválidos
     */
    public static function cleanString(string $string): string
    {
        // Primero, intentamos limpiar con mb_convert_encoding
        $cleaned = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        // Luego, usamos un regex más agresivo para eliminar caracteres inválidos
        $cleaned = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $cleaned);
        
        // Si aún hay problemas, usamos iconv
        if ($cleaned === null) {
            $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        }
        
        return $cleaned ?: '';
    }

    /**
     * Limpia recursivamente un array o objeto de caracteres UTF-8 inválidos
     */
    public static function clean($data)
    {
        if (is_string($data)) {
            return self::cleanString($data);
        }

        if (is_array($data)) {
            return array_map(function ($item) {
                return self::clean($item);
            }, $data);
        }

        if (is_object($data)) {
            $cleaned = clone $data;
            foreach ($cleaned as $key => $value) {
                $cleaned->$key = self::clean($value);
            }
            return $cleaned;
        }

        return $data;
    }

    /**
     * Verifica si una cadena contiene caracteres UTF-8 inválidos
     */
    public static function hasInvalidUtf8(string $string): bool
    {
        return !mb_check_encoding($string, 'UTF-8');
    }

    /**
     * Obtiene información sobre los caracteres problemáticos en una cadena
     */
    public static function getUtf8Info(string $string): array
    {
        $invalidPositions = [];
        $length = strlen($string);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $string[$i];
            $byte = ord($char);
            
            // Detectar bytes inválidos en UTF-8
            if ($byte < 0x80) {
                // ASCII válido
                continue;
            } elseif (($byte & 0xE0) == 0xC0) {
                // Carácter de 2 bytes
                if ($i + 1 >= $length) {
                    $invalidPositions[] = $i;
                    continue;
                }
                $byte2 = ord($string[$i + 1]);
                if (($byte2 & 0xC0) != 0x80) {
                    $invalidPositions[] = $i;
                }
                $i++; // Saltar el siguiente byte
            } elseif (($byte & 0xF0) == 0xE0) {
                // Carácter de 3 bytes
                if ($i + 2 >= $length) {
                    $invalidPositions[] = $i;
                    continue;
                }
                $byte2 = ord($string[$i + 1]);
                $byte3 = ord($string[$i + 2]);
                if (($byte2 & 0xC0) != 0x80 || ($byte3 & 0xC0) != 0x80) {
                    $invalidPositions[] = $i;
                }
                $i += 2; // Saltar los siguientes 2 bytes
            } elseif (($byte & 0xF8) == 0xF0) {
                // Carácter de 4 bytes
                if ($i + 3 >= $length) {
                    $invalidPositions[] = $i;
                    continue;
                }
                $byte2 = ord($string[$i + 1]);
                $byte3 = ord($string[$i + 2]);
                $byte4 = ord($string[$i + 3]);
                if (($byte2 & 0xC0) != 0x80 || ($byte3 & 0xC0) != 0x80 || ($byte4 & 0xC0) != 0x80) {
                    $invalidPositions[] = $i;
                }
                $i += 3; // Saltar los siguientes 3 bytes
            } else {
                // Byte inválido
                $invalidPositions[] = $i;
            }
        }

        return [
            'is_valid' => empty($invalidPositions),
            'invalid_positions' => $invalidPositions,
            'length' => $length,
            'has_invalid_chars' => !empty($invalidPositions),
        ];
    }
}
