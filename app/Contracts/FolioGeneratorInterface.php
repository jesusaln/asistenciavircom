<?php

namespace App\Contracts;

interface FolioGeneratorInterface
{
    /**
     * Generate the next sales number.
     *
     * @param int|null $almacenId Optional warehouse ID for warehouse-specific sequences
     * @return string The generated sales number (e.g., "V000123")
     */
    public function generarProximoFolio(?int $almacenId = null): string;
}
