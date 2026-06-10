<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ventas\VentaQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosController extends Controller
{
    protected $ventaQueryService;

    public function __construct(VentaQueryService $ventaQueryService)
    {
        $this->ventaQueryService = $ventaQueryService;
    }

    public function index(Request $request)
    {
        $data = $this->ventaQueryService->getCreateData($request);

        return Inertia::render('Admin/Pos/Index', $data);
    }
}
