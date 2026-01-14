<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use Spatie\Permission\Models\Role;
use Exception;
use App\Models\Almacen;
use App\Models\RegistroVacaciones;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
        $this->middleware(['auth:sanctum', 'verified'])->except(['index', 'show']);
    }

    public function profile()
    {
        $user = Auth::user()->load(['almacen_venta', 'almacen_compra']);
        $almacenes = Almacen::select('id', 'nombre')->where('estado', 'activo')->orderBy('nombre')->get();
        return Inertia::render('Profile/Show', [
            'user' => $user,
            'almacenes' => $almacenes,
            'sessions' => collect(),
            'confirmsTwoFactorAuthentication' => false
        ]);
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('viewAny', User::class);
        } catch (AuthorizationException $e) {
            return redirect()->route('panel')->with('error', 'No tienes permisos para ver la lista de usuarios.');
        } catch (Exception $e) {
            Log::error('Error en UserController@index: ' . $e->getMessage());
            return redirect()->route('panel')->with('error', 'Error al cargar la lista de usuarios.');
        }

        try {
            $query = User::with('roles');

            // Filtros de búsqueda
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            }

            // Filtrar por estado activo/inactivo
            if ($request->query->has('activo')) {
                $val = (string) $request->query('activo');
                if ($val === '1') {
                    $query->where(function ($query) {
                        $query->where('activo', true)->orWhereNull('activo');
                    });
                } elseif ($val === '0') {
                    $query->where('activo', false);
                }
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $validSort = ['name', 'email', 'created_at', 'activo'];

            if (!in_array($sortBy, $validSort))
                $sortBy = 'created_at';
            if (!in_array($sortDirection, ['asc', 'desc']))
                $sortDirection = 'desc';

            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $usuarios = $query->paginate(10)->appends($request->query());

            // Estadísticas
            $usuariosCount = User::count();
            $usuariosActivos = User::where(function ($q) {
                $q->where('activo', true)->orWhereNull('activo');
            })->count();

            return Inertia::render('Usuarios/Index', [
                'usuarios' => $usuarios,
                'stats' => [
                    'total' => $usuariosCount,
                    'activos' => $usuariosActivos,
                    'inactivos' => $usuariosCount - $usuariosActivos,
                ],
                'filters' => $request->only(['search', 'activo']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            ]);
        } catch (Exception $e) {
            Log::error('Error en UserController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la lista de usuarios.');
        }
    }

    public function create()
    {
        try {
            $this->authorize('create', User::class);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para crear usuarios.');
        } catch (Exception $e) {
            Log::error('Error en UserController@create: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al procesar la solicitud de crear usuario.');
        }

        $currentUser = Auth::user();
        $roles = Role::with('permissions')->get()->filter(function ($role) use ($currentUser) {
            // Solo super-admin puede ver/asignar el rol super-admin
            if ($role->name === 'super-admin' && !$currentUser->hasRole('super-admin')) {
                return false;
            }
            return true;
        })->map(function ($role) {
            // Group permissions by action for summary
            $permissionSummary = $role->permissions->groupBy(function ($perm) {
                $parts = explode(' ', $perm->name);
                return $parts[0] ?? 'otros';
            })->map(function ($group) {
                return $group->count();
            });

            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => ucfirst(str_replace(['_', '-'], ' ', $role->name)),
                'permissions_count' => $role->permissions->count(),
                'permissions_summary' => $permissionSummary,
                'permissions_list' => $role->permissions->take(5)->pluck('name')->map(fn($p) => ucfirst(str_replace('_', ' ', $p)))->toArray(),
            ];
        })->values(); // Reindexar array
        $almacenes = Almacen::where('estado', 'activo')->orderBy('nombre', 'asc')->get();

        return Inertia::render('Usuarios/Create', [
            'roles' => $roles,
            'almacenes' => $almacenes
        ]);
    }

    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('update', $user);

            $currentUser = Auth::user();
            $roles = Role::with('permissions')->get()->filter(function ($role) use ($currentUser) {
                // Solo super-admin puede ver/asignar el rol super-admin
                if ($role->name === 'super-admin' && !$currentUser->hasRole('super-admin')) {
                    return false;
                }
                return true;
            })->map(function ($role) {
                // Group permissions by action for summary
                $permissionSummary = $role->permissions->groupBy(function ($perm) {
                    $parts = explode(' ', $perm->name);
                    return $parts[0] ?? 'otros';
                })->map(function ($group) {
                    return $group->count();
                });

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => ucfirst(str_replace(['_', '-'], ' ', $role->name)),
                    'permissions_count' => $role->permissions->count(),
                    'permissions_summary' => $permissionSummary,
                    'permissions_list' => $role->permissions->take(5)->pluck('name')->map(fn($p) => ucfirst(str_replace('_', ' ', $p)))->toArray(),
                ];
            })->values();

            $almacenes = Almacen::where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre']);

            // Cargar los roles del usuario autenticado
            $authUser = User::with('roles')->find(Auth::id());

            // Obtener permisos agrupados para la matriz
            $permissionGroups = $this->getGroupedPermissions();

            // Obtener permisos directos del usuario (no heredados de roles)
            $userDirectPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            // Obtener permisos heredados de sus roles
            $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

            return Inertia::render('Usuarios/Edit', [
                'usuario' => $user->load('roles'),
                'roles' => $roles,
                'almacenes' => $almacenes,
                'auth' => ['user' => $authUser],
                'permissionGroups' => $permissionGroups,
                'userDirectPermissions' => $userDirectPermissions,
                'rolePermissions' => $rolePermissions,
            ]);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para editar este usuario.');
        } catch (Exception $e) {
            Log::error('Error en UserController@edit: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al cargar el usuario para editar.');
        }
    }

    public function store(Request $request)
    {
        try {
            $this->authorize('create', User::class);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para crear usuarios.');
        } catch (Exception $e) {
            Log::error('Error en UserController@store: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al procesar la solicitud de crear usuario.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'almacen_venta_id' => 'nullable|exists:almacenes,id',
            'almacen_compra_id' => 'nullable|exists:almacenes,id',
            'roles' => 'nullable|array',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'activo' => true,
            'almacen_venta_id' => $validated['almacen_venta_id'] ?? null,
            'almacen_compra_id' => $validated['almacen_compra_id'] ?? null,
            'password' => Hash::make($validated['password']),
        ];

        $user = User::create($userData);

        if ($request->has('roles')) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('update', $user);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para editar este usuario.');
        } catch (Exception $e) {
            Log::error('Error en UserController@update: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al cargar el usuario para editar.');
        }

        Log::debug('UserController@update - Request data', [
            'user_id' => $id,
            'es_empleado' => $request->input('es_empleado'),
            'has_password' => !empty($request->input('password')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'almacen_venta_id' => 'nullable|exists:almacenes,id',
            'almacen_compra_id' => 'nullable|exists:almacenes,id',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
        ]);

        // Actualizar los datos del usuario
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'almacen_venta_id' => $validated['almacen_venta_id'] ?? null,
            'almacen_compra_id' => $validated['almacen_compra_id'] ?? null,
            'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($validated['roles']);
        }

        $tipo = $user->es_empleado ? 'empleado' : 'usuario';
        return redirect()->route('usuarios.index')->with('success', ucfirst($tipo) . ' actualizado exitosamente.');
    }

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('view', $user);
            return Inertia::render('Usuarios/Profile', ['usuario' => $user]);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para ver este usuario.');
        } catch (Exception $e) {
            Log::error('Error en UserController@show: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al cargar el usuario.');
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('delete', $user);
        } catch (AuthorizationException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para eliminar este usuario.');
        } catch (Exception $e) {
            Log::error('Error en UserController@destroy: ' . $e->getMessage());
            return redirect()->route('usuarios.index')->with('error', 'Error al cargar el usuario para eliminar.');
        }

        try {
            $user->delete();
            return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (QueryException $e) {
            return redirect()->route('usuarios.index')->with('error', 'No se pudo eliminar el usuario debido a restricciones de la base de datos.');
        } catch (Exception $e) {
            return redirect()->route('usuarios.index')->with('error', 'Ocurrió un error inesperado.');
        }
    }

    public function toggle(User $user)
    {
        try {
            $this->authorize('update', $user);
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'No tienes permisos para cambiar el estado de este usuario.');
        } catch (Exception $e) {
            Log::error('Error en UserController@toggle: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del usuario.');
        }

        try {
            $user->update(['activo' => !$user->activo]);
            return redirect()->back()->with('success', $user->activo ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente');
        } catch (Exception $e) {
            Log::error('Error cambiando estado del usuario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Hubo un problema al cambiar el estado del usuario.');
        }
    }

    public function updateAlmacenVenta(Request $request)
    {
        $request->validate([
            'almacen_venta_id' => 'nullable|exists:almacenes,id',
        ]);

        $user = Auth::user();
        $user->update([
            'almacen_venta_id' => $request->almacen_venta_id,
        ]);

        $almacenVenta = null;
        if ($request->almacen_venta_id) {
            $almacenVenta = Almacen::find($request->almacen_venta_id);
        }

        return response()->json([
            'success' => true,
            'almacen_venta' => $almacenVenta,
            'message' => 'Almacén de venta actualizado correctamente.'
        ]);
    }

    public function updateAlmacenCompra(Request $request)
    {
        $request->validate([
            'almacen_compra_id' => 'nullable|exists:almacenes,id',
        ]);

        $user = Auth::user();
        $user->update([
            'almacen_compra_id' => $request->almacen_compra_id,
        ]);

        $almacenCompra = null;
        if ($request->almacen_compra_id) {
            $almacenCompra = Almacen::find($request->almacen_compra_id);
        }

        return response()->json([
            'success' => true,
            'almacen_compra' => $almacenCompra,
            'message' => 'Almacén de compra actualizado correctamente.'
        ]);
    }

    /**
     * Actualizar almacén de venta de un usuario (solo admin)
     */
    public function updateUserAlmacenVenta(Request $request, $userId)
    {
        $authUser = Auth::user();
        if (!$authUser->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'almacen_venta_id' => 'nullable|exists:almacenes,id',
        ]);

        $user = User::findOrFail($userId);
        $user->update([
            'almacen_venta_id' => $request->almacen_venta_id ?: null,
        ]);

        $almacenVenta = null;
        if ($request->almacen_venta_id) {
            $almacenVenta = Almacen::find($request->almacen_venta_id);
        }

        return response()->json([
            'success' => true,
            'almacen_venta' => $almacenVenta,
            'message' => 'Almacén de venta actualizado correctamente.'
        ]);
    }

    /**
     * Actualizar almacén de compra de un usuario (solo admin)
     */
    public function updateUserAlmacenCompra(Request $request, $userId)
    {
        $authUser = Auth::user();
        if (!$authUser->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'almacen_compra_id' => 'nullable|exists:almacenes,id',
        ]);

        $user = User::findOrFail($userId);
        $user->update([
            'almacen_compra_id' => $request->almacen_compra_id ?: null,
        ]);

        $almacenCompra = null;
        if ($request->almacen_compra_id) {
            $almacenCompra = Almacen::find($request->almacen_compra_id);
        }

        return response()->json([
            'success' => true,
            'almacen_compra' => $almacenCompra,
            'message' => 'Almacén de compra actualizado correctamente.'
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', User::class);

        try {
            $query = User::with('roles');

            // Aplicar los mismos filtros que en index
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            }

            if ($request->query->has('activo')) {
                $val = (string) $request->query('activo');
                if ($val === '1') {
                    $query->where(function ($query) {
                        $query->where('activo', true)->orWhereNull('activo');
                    });
                } elseif ($val === '0') {
                    $query->where('activo', false);
                }
            }

            $usuarios = $query->get();

            $filename = 'usuarios_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($usuarios) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Email',
                    'Rol',
                    'Activo',
                    'Fecha Creación'
                ]);

                foreach ($usuarios as $usuario) {
                    fputcsv($file, [
                        $usuario->id,
                        $usuario->name,
                        $usuario->email,
                        $usuario->getRoleNames()->first() ?? 'Sin rol',
                        $usuario->activo ? 'Sí' : 'No',
                        $usuario->created_at?->format('d/m/Y H:i:s')
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error en exportación de usuarios: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al exportar los usuarios.');
        }
    }

    /**
     * Sync user direct permissions
     */
    public function syncPermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $this->authorize('update', $user);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        } catch (Exception $e) {
            Log::error('Error en UserController@syncPermissions: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cargar el usuario'], 500);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            // Sync only direct permissions (not role-based)
            $user->syncPermissions($validated['permissions'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Permisos actualizados correctamente',
                'userDirectPermissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
            ]);
        } catch (Exception $e) {
            Log::error('Error sincronizando permisos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar permisos'], 500);
        }
    }

    /**
     * Helper para agrupar permisos (copiado de RoleController)
     */
    private function getGroupedPermissions()
    {
        $permissions = Permission::all();

        $actions = ['view', 'create', 'edit', 'delete', 'export', 'stats', 'manage'];

        $grouped = [];

        foreach ($permissions as $perm) {
            $name = $perm->name;
            $foundAction = 'other';
            $module = $name;

            foreach ($actions as $action) {
                if (str_starts_with($name, $action . ' ')) {
                    $foundAction = $action;
                    $module = substr($name, strlen($action) + 1);
                    break;
                }
            }

            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'module' => $module,
                    'label' => ucfirst(str_replace(['_', '-'], ' ', $module)),
                    'permissions' => []
                ];
            }

            $grouped[$module]['permissions'][$foundAction] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'label' => $this->formatPermissionLabel($perm->name),
                'action' => $foundAction
            ];
        }

        ksort($grouped);

        return array_values($grouped);
    }

    private function formatPermissionLabel($name)
    {
        $colloquial = [
            'view' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
            'export' => 'Exportar',
            'stats' => 'Estadísticas',
            'manage' => 'Gestionar'
        ];

        foreach ($colloquial as $key => $val) {
            if (str_starts_with($name, $key)) {
                return str_replace($key, $val, $name);
            }
        }

        return ucfirst($name);
    }
}
