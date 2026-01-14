<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Minimal Feature test to isolate timeout issue
 */
class MinimalFeatureTest extends TestCase
{
    /**
     * Test: Just test application boots
     */
    public function test_application_boots(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test: Application returns 200 on root (might redirect)
     */
    public function test_root_responds(): void
    {
        $response = $this->get('/');
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302, 401]));
    }
}
