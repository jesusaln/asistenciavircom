<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\User;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name'),
                'created_at' => $role->created_at,
            ];
        });

        return Inertia::render('Roles/Index', [
            'roles' => $roles
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Roles/Create', [
            'permissionGroups' => $this->getGroupedPermissions()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        // Evitar editar roles críticos si se desea, por ahora permitido

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')
            ],
            'permissionGroups' => $this->getGroupedPermissions()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        // Evitar renombrar roles sistema clave si es necesario
        if (in_array($role->name, ['admin', 'super-admin']) && $validated['name'] !== $role->name) {
            return back()->with('error', 'No se puede renombrar roles de sistema críticos.');
        }

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // Prevenir eliminar admin
        if ($role->name === 'admin') {
            return back()->with('error', 'No se puede eliminar el rol de Administrador.');
        }

        // Verificar si hay usuarios con este rol
        if ($role->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un rol que tiene usuarios asignados.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente.');
    }

    /**
     * Show role details with assigned users
     */
    public function show(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        // Obtener usuarios con este rol manualmente (evita el bug de getModelForGuard)
        $usersWithRole = User::whereHas('roles', function ($query) use ($role) {
            $query->where('roles.id', $role->id);
        })->get(['id', 'name', 'email']);

        // Get all users that can be assigned to this role
        $allUsers = User::where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Roles/Show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'permissions_count' => $role->permissions->count(),
                'users' => $usersWithRole->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
                'users_count' => $usersWithRole->count(),
                'created_at' => $role->created_at,
            ],
            'allUsers' => $allUsers,
        ]);
    }

    /**
     * Sync users to a role
     */
    public function syncUsers(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Cannot modify super-admin role unless requester is super-admin
        if ($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'No tienes permisos para modificar el rol de Super Administrador.');
        }

        // Get the users
        $users = User::whereIn('id', $validated['user_ids'])->get();

        // Clear all current users from this role and assign new ones
        // First, remove this role from all users that have it
        foreach ($role->users as $user) {
            $user->removeRole($role);
        }

        // Then assign the role to the selected users
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        return back()->with('success', 'Usuarios del rol actualizados exitosamente.');
    }

    /**
     * Add a single user to a role
     */
    public function addUser(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Cannot assign super-admin role unless requester is super-admin
        if ($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'No tienes permisos para asignar el rol de Super Administrador.');
        }

        $user->assignRole($role);

        return back()->with('success', "Usuario {$user->name} agregado al rol {$role->name}.");
    }

    /**
     * Remove a single user from a role
     */
    public function removeUser(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);

        // Cannot modify super-admin role unless requester is super-admin
        if ($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'No tienes permisos para modificar el rol de Super Administrador.');
        }

        $user->removeRole($role);

        return back()->with('success', "Usuario {$user->name} removido del rol {$role->name}.");
    }

    /**
     * Helper para agrupar permisos
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

        // Sort by module label
        ksort($grouped);

        return array_values($grouped);
    }

    private function formatPermissionLabel($name)
    {
        // Formatear nombre para mostrar bonito
        // view usuarios -> Ver Usuarios
        // create clientes -> Crear Clientes

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
