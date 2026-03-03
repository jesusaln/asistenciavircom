<?php

namespace App\Contracts;

interface RustDeskClientInterface
{
    public function getDeviceStatus(string $rustdeskId): array;

    public function listDevices(?string $search = null): array;

    public function syncAlias(string $rustdeskId, string $alias): bool;
}

