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
    public function __construct()
    {
        $this->authorizeResource(Proyecto::class, 'proyecto');
    }

    /**
     * Listado de Proyectos (Propios y Compartidos)
     */
    public function index()
    {
        // El constructor ya autoriza 'viewAny'

        $user = Auth::user();

        // ... (resto del código igual) ...

        // Proyectos propios
        $misProyectos = $user->ownedProjects;

        // Proyectos compartidos
        $proyectosCompartidos = $user->joinedProjects()->with('cliente')->get();

        // Cargar proyectos propios con cliente
        $misProyectosConCliente = $user->ownedProjects()->with('cliente')->get();

        // Lista de clientes para el formulario
        $clientes = Cliente::orderBy('nombre_razon_social')->get(['id', 'nombre_razon_social', 'rfc']);

        return Inertia::render('Proyecto/Index', [
            'misProyectos' => $misProyectosConCliente,
            'proyectosCompartidos' => $proyectosCompartidos,
            'clientes' => $clientes,
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
    /**
     * Ver tablero del proyecto (Roadmap)
     */
    public function show(Proyecto $proyecto)
    {
        $user = Auth::user();

        // Validar acceso: Manejado por Policy

        // Cargar tareas agrupadas
        $tareas = $proyecto->tareas()->orderBy('orden')->get();

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
            ->get();

        $totalGastos = $gastos->sum('total');

        // Lista de usuarios para compartir (excluyendo al dueño)
        $usuarios = User::where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Cargar productos del proyecto con datos del producto
        $productosProyecto = $proyecto->productos;

        // Lista de productos disponibles para agregar
        $productosDisponibles = \App\Models\Producto::select('id', 'nombre', 'codigo', 'precio_venta')
            ->orderBy('nombre')
            ->take(100)
            ->get();

        $totalProductos = $proyecto->total_productos;

        // Lista de categorías de gasto
        $categoriasGasto = \App\Models\CategoriaGasto::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Proyecto/Roadmap', [
            'proyecto' => $proyecto,
            'columnas' => $columnas,
            'members' => $proyecto->members,
            'isOwner' => $proyecto->owner_id === $user->id,
            'gastos' => $gastos,
            'totalGastos' => $totalGastos,
            'usuarios' => $usuarios,
            'productosProyecto' => $productosProyecto,
            'productosDisponibles' => $productosDisponibles,
            'totalProductos' => $totalProductos,
            'categoriasGasto' => $categoriasGasto,
        ]);
    }

    /**
     * Actualizar Proyecto
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        // Autorización manejada por Policy

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
        // Autorización manejada por Policy

        $proyecto->delete();

        return redirect()->route('proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    /**
     * Compartir Proyecto
     */
    public function share(Request $request, Proyecto $proyecto)
    {
        $this->authorize('update', $proyecto);

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
        $this->authorize('update', $proyecto);

        $proyecto->members()->detach($user->id);

        return redirect()->back()->with('success', 'Usuario removido del proyecto.');
    }

    /**
     * Agregar producto al proyecto
     */
    public function addProducto(Request $request, Proyecto $proyecto)
    {
        // Usamos 'view' porque cualquier miembro puede agregar productos
        $this->authorize('view', $proyecto);

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
        // Usamos 'view' porque cualquier miembro puede quitar productos (o update si quieres ser estricto)
        $this->authorize('view', $proyecto);

        $proyecto->productos()->detach($productoId);

        return redirect()->back()->with('success', 'Producto eliminado del proyecto.');
    }

    /**
     * Agregar gasto al proyecto
     */
    public function addGasto(Request $request, Proyecto $proyecto)
    {
        $this->authorize('view', $proyecto);

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
