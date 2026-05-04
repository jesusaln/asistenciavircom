<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\ProyectoTarea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;

class ProyectoController extends Controller
{
    /**
     * Listado de Proyectos (Propios y Compartidos)
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin') && !\Illuminate\Support\Facades\Gate::allows('view proyectos')) {
            abort(403);
        }
        $maxRows = 500;

        // Query base dependiendo del rol
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $misProyectosQuery = Proyecto::query();
            $proyectosCompartidos = collect(); // Los admins ven todo en la sección principal
        } else {
            $misProyectosQuery = $user->ownedProjects();
            $proyectosCompartidos = $user->joinedProjects()->with('cliente')->limit($maxRows)->get();
        }

        $misProyectosConCliente = $misProyectosQuery->with(['cliente', 'owner'])->limit($maxRows)->get();

        // Lista de clientes para el formulario (Limited for performance)
        $clientes = Cliente::orderBy('nombre_razon_social')->limit(500)->get(['id', 'nombre_razon_social', 'rfc']);

        return Inertia::render('Proyecto/Index', [
            'misProyectos' => $misProyectosConCliente,
            'proyectosCompartidos' => $proyectosCompartidos,
            'clientes' => $clientes,
            'truncated' => [
                'mis_proyectos' => $misProyectosQuery->count() > $maxRows,
                'proyectos_compartidos' => $user->joinedProjects()->count() > $maxRows,
            ],
        ]);
    }

    /**
     * Crear nuevo proyecto
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $proyecto = Proyecto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'color' => $request->color ?? '#fbbf24',
            'cliente_id' => $request->cliente_id,
            'owner_id' => Auth::id()
        ]);

        return redirect()->route('proyectos.show', $proyecto->id)
            ->with('success', 'Proyecto creado exitosamente.');
    }

    /**
     * Ver tablero del proyecto (Roadmap)
     */
    public function show(Proyecto $proyecto)
    {
        $user = Auth::user();
        $maxRows = 500;

        // Validar acceso (Dueño o Miembro)
        if ($proyecto->owner_id !== $user->id && !$proyecto->members->contains($user->id)) {
            abort(403, 'No tienes permiso para ver este proyecto.');
        }

        // Cargar tareas agrupadas
        $tareas = $proyecto->tareas()->orderBy('orden')->limit($maxRows)->get();

        $columnas = [
            'sugerencias' => $tareas->where('estado', 'sugerencias')->values(),
            'pendiente' => $tareas->where('estado', 'pendiente')->values(),
            'en_progreso' => $tareas->where('estado', 'en_progreso')->values(),
            'completado' => $tareas->where('estado', 'completado')->values(),
        ];

        // Cargar gastos asociados al proyecto
        $gastos = \App\Models\Compra::with(['categoriaGasto', 'proveedor'])
            ->where('tipo', 'gasto')
            ->where('proyecto_id', $proyecto->id)
            ->where('estado', 'procesada')
            ->orderBy('fecha_compra', 'desc')
            ->limit($maxRows)
            ->get();

        $totalGastos = $gastos->sum('total');

        // Lista de usuarios para compartir (excluyendo al dueño) (Limited for performance)
        $usuarios = User::where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email']);

        // Cargar productos del proyecto con datos del producto
        $productosProyecto = $proyecto->productos()->limit($maxRows)->get();

        // Lista de productos disponibles para agregar
        $productosDisponibles = \App\Models\Producto::select('id', 'nombre', 'codigo', 'precio_venta')
            ->orderBy('nombre')
            ->take(100)
            ->get();

        $totalProductos = $proyecto->total_productos;

        // Lista de categorías de gasto
        $categoriasGasto = \App\Models\CategoriaGasto::orderBy('nombre')->limit($maxRows)->get(['id', 'nombre']);

        return Inertia::render('Proyecto/Roadmap', [
            'proyecto' => $proyecto,
            'columnas' => $columnas,
            'members' => $proyecto->members,
            'isOwner' => $proyecto->owner_id === $user->id || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'gastos' => $gastos,
            'totalGastos' => $totalGastos,
            'usuarios' => $usuarios,
            'productosProyecto' => $productosProyecto,
            'productosDisponibles' => $productosDisponibles,
            'totalProductos' => $totalProductos,
            'categoriasGasto' => $categoriasGasto,
            'truncated' => [
                'tareas' => $proyecto->tareas()->count() > $maxRows,
                'gastos' => \App\Models\Compra::where('tipo', 'gasto')->where('proyecto_id', $proyecto->id)->where('estado', 'procesada')->count() > $maxRows,
                'productos' => $proyecto->productos()->count() > $maxRows,
                'categorias_gasto' => \App\Models\CategoriaGasto::count() > $maxRows,
            ],
        ]);
    }

    /**
     * Actualizar Proyecto
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        // $this->authorize('update', $proyecto); // Implementar Policy luego si es necesario simple check aqui

        if ($proyecto->owner_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'color' => 'nullable|string',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $proyecto->update($request->only('nombre', 'descripcion', 'color', 'cliente_id'));

        return redirect()->back()->with('success', 'Proyecto actualizado.');
    }

    /**
     * Eliminar Proyecto
     */
    public function destroy(Proyecto $proyecto)
    {
        $user = Auth::user();
        
        // El dueño o un administrador pueden eliminar
        if ($proyecto->owner_id !== $user->id && !$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            abort(403);
        }

        // Verificar si tiene gastos asociados (registros financieros)
        if (\App\Models\Compra::where('proyecto_id', $proyecto->id)->exists()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar el proyecto porque tiene gastos/compras asociadas.']);
        }

        // Verificar si tiene tareas en progreso o completadas
        if ($proyecto->tareas()->whereIn('estado', ['en_progreso', 'completado'])->exists()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar el proyecto con tareas en progreso o terminadas.']);
        }

        @\Illuminate\Support\Facades\Log::info('Proyecto eliminado', [
            'proyecto_id' => $proyecto->id,
            'nombre' => $proyecto->nombre,
            'eliminado_por' => Auth::id(),
            'ip' => request()->ip()
        ]);

        $proyecto->delete();

        return redirect()->route('proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    /**
     * Compartir Proyecto
     */
    public function share(Request $request, Proyecto $proyecto)
    {
        if ($proyecto->owner_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:editor,viewer'
        ]);

        $userId = $request->user_id;

        if ($proyecto->members->contains($userId)) {
            // Actualizar rol si ya existe
            $proyecto->members()->updateExistingPivot($userId, ['role' => $request->role]);
            return redirect()->back()->with('success', 'Permisos actualizados.');
        } else {
            // Adjuntar nuevo miembro
            $proyecto->members()->attach($userId, ['role' => $request->role]);
            return redirect()->back()->with('success', 'Usuario agregado al proyecto.');
        }
    }

    /**
     * Remover miembro
     */
    public function removeMember(Proyecto $proyecto, User $user)
    {
        if ($proyecto->owner_id !== Auth::id()) {
            abort(403);
        }

        $proyecto->members()->detach($user->id);

        return redirect()->back()->with('success', 'Usuario removido del proyecto.');
    }

    /**
     * Agregar producto al proyecto
     */
    public function addProducto(Request $request, Proyecto $proyecto)
    {
        if ($proyecto->owner_id !== Auth::id() && !$proyecto->members->contains(Auth::id())) {
            abort(403);
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.01',
            'precio_unitario' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);

        // Obtener precio del producto si no se especifica
        $producto = \App\Models\Producto::find($request->producto_id);
        $precio = $request->precio_unitario ?? $producto->precio_venta ?? 0;

        // Agregar o actualizar el producto en el proyecto
        $proyecto->productos()->syncWithoutDetaching([
            $request->producto_id => [
                'cantidad' => $request->cantidad,
                'precio_unitario' => $precio,
                'notas' => $request->notas,
            ]
        ]);

        return redirect()->back()->with('success', 'Producto agregado al proyecto.');
    }

    /**
     * Eliminar producto del proyecto
     */
    public function removeProducto(Proyecto $proyecto, $productoId)
    {
        if ($proyecto->owner_id !== Auth::id() && !$proyecto->members->contains(Auth::id())) {
            abort(403);
        }

        $proyecto->productos()->detach($productoId);

        return redirect()->back()->with('success', 'Producto eliminado del proyecto.');
    }

    /**
     * Agregar gasto al proyecto
     */
    public function addGasto(Request $request, Proyecto $proyecto)
    {
        if ($proyecto->owner_id !== Auth::id() && !$proyecto->members->contains(Auth::id())) {
            abort(403);
        }

        $request->validate([
            'total' => 'required|numeric|min:0.01',
            'categoria_gasto_id' => 'nullable|exists:categoria_gastos,id',
            'fecha_compra' => 'nullable|date',
            'descripcion' => 'nullable|string',
        ]);

        // Crear gasto como registro en compras con tipo 'gasto'
        \App\Models\Compra::create([
            'tipo' => 'gasto',
            'proyecto_id' => $proyecto->id,
            'categoria_gasto_id' => $request->categoria_gasto_id,
            'fecha_compra' => $request->fecha_compra ?? now(),
            'total' => $request->total,
            'subtotal' => $request->total,
            'iva' => 0,
            'notas' => $request->descripcion,
            'estado' => 'procesada',
        ]);

        return redirect()->back()->with('success', 'Gasto agregado al proyecto.');
    }

    /**
     * Eliminar gasto del proyecto
     */
    public function removeGasto(Proyecto $proyecto, $gastoId)
    {
        if ($proyecto->owner_id !== Auth::id() && !$proyecto->members->contains(Auth::id())) {
            abort(403);
        }

        $gasto = \App\Models\Compra::where('proyecto_id', $proyecto->id)
            ->where('tipo', 'gasto')
            ->findOrFail($gastoId);

        $gasto->delete();

        return redirect()->back()->with('success', 'Gasto eliminado del proyecto.');
    }

    public function addCategoriaGasto(\Illuminate\Http\Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        \App\Models\CategoriaGasto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'empresa_id' => $user->empresa_id ?? 8,
            'activo' => true,
        ]);

        return redirect()->back()->with('success', 'Categoría de gasto creada con éxito.');
    }
}
