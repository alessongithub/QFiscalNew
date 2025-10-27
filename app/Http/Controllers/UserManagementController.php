<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        // Apenas admin (papel do tenant) pode gerenciar usuários
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.view'), 403);
        $user = auth()->user();
        $tenant = $user->tenant;

        $users = User::where('tenant_id', $tenant->id)->paginate(10);
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $rolesPayload = $roles->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'permissions' => $r->permissions->pluck('id')->values()->all(),
            ];
        })->values()->all();
        $permissionsPayload = $permissions->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
            ];
        })->values()->all();

        // Limite de usuários pelo plano
        $plan = $tenant->plan;
        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
        $maxUsers = $features['max_users'] ?? 1;
        $currentUsers = User::where('tenant_id', $tenant->id)->count();

        return view('users.index', compact('users', 'roles', 'permissions', 'maxUsers', 'currentUsers', 'rolesPayload', 'permissionsPayload'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.create'), 403);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $tenant = auth()->user()->tenant;

        // Verificar limite de usuários do plano
        $plan = $tenant->plan;
        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
        $maxUsers = $features['max_users'] ?? 1;
        $currentUsers = User::where('tenant_id', $tenant->id)->count();
        if ($maxUsers !== -1 && $currentUsers >= $maxUsers) {
            return back()->with('error', 'Limite de usuários do seu plano foi atingido. Faça upgrade para adicionar mais usuários.');
        }

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        if ($request->filled('role_id')) {
            $newUser->roles()->sync([$request->role_id]);
        }

        if ($request->filled('permissions')) {
            $newUser->permissions()->sync($request->permissions);
        }

        return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.view'), 403);
        $tenant = auth()->user()->tenant;
        abort_unless($user->tenant_id === $tenant->id, 403);

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $rolesPayload = $roles->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'permissions' => $r->permissions->pluck('id')->values()->all(),
            ];
        })->values()->all();
        $permissionsPayload = $permissions->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
            ];
        })->values()->all();
        return view('users.edit', compact('user', 'roles', 'permissions', 'rolesPayload', 'permissionsPayload'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);
        $tenant = auth()->user()->tenant;
        abort_unless($user->tenant_id === $tenant->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'permissions' => 'array',
            'permissions[*]' => 'exists:permissions,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($request->filled('role_id')) {
            $user->roles()->sync([$request->role_id]);
        } else {
            $user->roles()->sync([]);
        }

        $user->permissions()->sync($request->input('permissions', []));

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.delete'), 403);
        $tenant = auth()->user()->tenant;
        abort_unless($user->tenant_id === $tenant->id, 403);
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso');
    }
}


