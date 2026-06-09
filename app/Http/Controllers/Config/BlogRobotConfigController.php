<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;

class BlogRobotConfigController extends Controller
{
    /**
     * Update the incoming blog robot configuration.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'blog_robot_token' => 'nullable|string',
            'blog_robot_enabled' => 'boolean',
        ]);

        $config = EmpresaConfiguracion::first(); // Assuming single company setup
        if ($config) {
            $config->update($validated);
        }

        return back()->with('success', 'Configuración de Robot de Blog actualizada correctamente.');
    }
}
