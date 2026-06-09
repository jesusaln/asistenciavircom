<?php

namespace App\Console\Commands;

use App\Support\VersionHelper;
use Illuminate\Console\Command;

class InertiaVersionCommand extends Command
{
    protected $signature = 'inertia:version';

    protected $description = 'Muestra la versión de assets usada por Inertia';

    public function handle(): int
    {
        $this->line(VersionHelper::getVersion());
        return self::SUCCESS;
    }
}

