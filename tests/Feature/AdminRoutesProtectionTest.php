<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminRoutesProtectionTest extends TestCase
{
    /**
     * @dataProvider protectedAdminRoutes
     */
    public function test_guest_is_redirected_from_admin_routes(string $uri): void
    {
        $this->get($uri)->assertStatus(302);
    }

    public static function protectedAdminRoutes(): array
    {
        return [
            ['/dashboard'],
            ['/panel'],
            ['/finanzas'],
            ['/clientes'],
            ['/proveedores'],
        ];
    }
}
