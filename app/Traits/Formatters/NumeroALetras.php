<?php

namespace App\Traits\Formatters;

trait NumeroALetras
{
    /**
     * Convertir número a letras (función robusta para moneda MXN)
     */
    public function numeroALetras($numero)
    {
        $n = is_numeric($numero) ? (float) $numero : 0.0;
        $entero = (int) floor($n);
        $decimales = (int) round(($n - $entero) * 100);
        if ($decimales === 100) {
            $entero++;
            $decimales = 0;
        }

        $palabras = $entero === 0 ? 'cero' : $this->enteroALetras($entero);
        $pesoWord = $entero === 1 ? 'PESO' : 'PESOS';

        if ($decimales > 0) {
            return mb_strtoupper(trim($palabras).' '.$pesoWord.' '.str_pad((string) $decimales, 2, '0', STR_PAD_LEFT).'/100 M.N.');
        }

        return mb_strtoupper(trim($palabras).' '.$pesoWord.' 00/100 M.N.');
    }

    /**
     * Solo la parte entera en palabras (sin sufijo de moneda). Usado en recursión.
     */
    protected function enteroALetras(int $entero): string
    {
        if ($entero === 0) {
            return '';
        }

        $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
        $especiales = [
            11 => 'once',
            12 => 'doce',
            13 => 'trece',
            14 => 'catorce',
            15 => 'quince',
            16 => 'dieciséis',
            17 => 'diecisiete',
            18 => 'dieciocho',
            19 => 'diecinueve',
            21 => 'veintiuno',
            22 => 'veintidós',
            23 => 'veintitrés',
            24 => 'veinticuatro',
            25 => 'veinticinco',
            26 => 'veintiséis',
            27 => 'veintisiete',
            28 => 'veintiocho',
            29 => 'veintinueve',
        ];

        $letras = '';

        if ($entero >= 1000000) {
            $millones = intdiv($entero, 1000000);
            if ($millones === 1) {
                $letras = 'un millón';
            } else {
                $letras = $this->enteroALetras($millones).' millones';
            }
            $entero %= 1000000;
        }

        if ($entero >= 1000) {
            $miles = intdiv($entero, 1000);
            if ($letras !== '') {
                $letras .= ' ';
            }
            if ($miles === 1) {
                $letras .= 'mil';
            } else {
                $letras .= $this->enteroALetras($miles).' mil';
            }
            $entero %= 1000;
        }

        if ($entero >= 100) {
            if ($letras !== '') {
                $letras .= ' ';
            }
            if ($entero === 100) {
                $letras .= 'cien';
            } else {
                $letras .= $centenas[intdiv($entero, 100)];
            }
            $entero %= 100;
        }

        if ($entero > 0) {
            if ($letras !== '') {
                $letras .= ' ';
            }

            if (isset($especiales[$entero])) {
                $letras .= $especiales[$entero];
            } elseif ($entero >= 10) {
                $letras .= $decenas[intdiv($entero, 10)];
                if ($entero % 10 > 0) {
                    $letras .= ' y '.$unidades[$entero % 10];
                }
            } else {
                $letras .= $unidades[$entero];
            }
        }

        return trim($letras);
    }
}
