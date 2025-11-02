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
        \Log::info('users.store: start', ['actor_id' => auth()->id(), 'payload' => $request->except(['password'])]);
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
        try {
            $tenant = auth()->user()->tenant;

            // Verificar limite de usuários do plano
            $plan = $tenant->plan;
            $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
            $maxUsers = (int) ($features['max_users'] ?? 1);
            $currentUsers = User::where('tenant_id', $tenant->id)->count();
            if ($maxUsers !== -1 && $currentUsers >= $maxUsers) {
                \Log::warning('users.store: plan limit reached', ['tenant_id' => $tenant->id, 'max' => $maxUsers, 'current' => $currentUsers]);
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

            // Auditoria: criação de usuário
            \App\Models\UserAudit::create([
                'actor_user_id' => auth()->id(),
                'target_user_id' => $newUser->id,
                'action' => 'created_user',
                'changes' => [
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'roles' => $newUser->roles()->pluck('slug')->all(),
                    'permissions' => $newUser->permissions()->pluck('slug')->all(),
                ],
                'notes' => 'Usuário criado',
            ]);

            \Log::info('users.store: success', ['new_user_id' => $newUser->id]);
            return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso');
        } catch (\Throwable $e) {
            \Log::error('users.store: exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erro ao criar usuário: ' . $e->getMessage())->withInput();
        }
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

        $before = $user->replicate();
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

        // Auditoria: atualização de usuário (dados/roles/permissões)
        $changes = [];
        foreach (['name','email'] as $f) {
            if ($before->$f !== $user->$f) {
                $changes[$f] = ['old' => $before->$f, 'new' => $user->$f];
            }
        }
        $beforeRoles = $before->roles()->pluck('slug')->all();
        $afterRoles = $user->roles()->pluck('slug')->all();
        if ($beforeRoles !== $afterRoles) {
            $changes['roles'] = ['old' => $beforeRoles, 'new' => $afterRoles];
        }
        $beforePerms = $before->permissions()->pluck('slug')->all();
        $afterPerms = $user->permissions()->pluck('slug')->all();
        if ($beforePerms !== $afterPerms) {
            $changes['permissions'] = ['old' => $beforePerms, 'new' => $afterPerms];
        }
        if (!empty($changes)) {
            \App\Models\UserAudit::create([
                'actor_user_id' => auth()->id(),
                'target_user_id' => $user->id,
                'action' => 'updated_user',
                'changes' => $changes,
                'notes' => 'Usuário atualizado',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->hasRoleSlug('admin'), 403);
        abort_unless(auth()->user()->hasPermission('users.delete'), 403);
        $tenant = auth()->user()->tenant;
        abort_unless($user->tenant_id === $tenant->id, 403);
        $snapshot = ['name' => $user->name, 'email' => $user->email, 'roles' => $user->roles()->pluck('slug')->all()];
        $user->delete();
        \App\Models\UserAudit::create([
            'actor_user_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'deleted_user',
            'changes' => $snapshot,
            'notes' => 'Usuário excluído',
        ]);
        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso');
    }
}


