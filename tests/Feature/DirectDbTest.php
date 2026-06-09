<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Test DB access WITHOUT DatabaseTransactions trait
 */
class DirectDbTest extends TestCase
{
    // NO DatabaseTransactions trait!

    /**
     * Test: Direct DB query works
     */
    public function test_direct_db_query(): void
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Test: Can count sat_estados table
     */
    public function test_can_query_sat_estados(): void
    {
        $count = DB::table('sat_estados')->count();
        $this->assertIsInt($count);
    }

    /**
     * Test: Can query clientes table
     */
    public function test_can_query_clientes(): void
    {
        $count = DB::table('clientes')->count();
        $this->assertIsInt($count);
    }
}
