<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\StorageLimitCheck;

class ClientController extends Controller
{
    use StorageLimitCheck;
    public function search(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('clients.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $term = trim((string)$request->get('term',''));
        $q = Client::where('tenant_id',$tenantId);
        if ($term !== '') {
            $q->where(function($qq) use ($term){
                $qq->where('name','like',"%{$term}%")
                   ->orWhere('cpf_cnpj','like',"%{$term}%");
            });
        }
        $clients = $q->orderBy('name')->limit(10)->get(['id','name','cpf_cnpj']);
        return response()->json($clients);
    }
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('clients.view'), 403);
        // Verificar se o usuário tem um tenant associado
        $user = auth()->user();
        if (!$user->tenant_id) {
            return redirect()->route('dashboard')->with('error', 'Usuário não possui empresa associada.');
        }
        
        // Filtrar apenas clientes do tenant atual
        $query = Client::where('tenant_id', $user->tenant_id);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ordenação e paginação configuráveis
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        if (!in_array($sortDirection, ['asc','desc'], true)) { $sortDirection = 'asc'; }
        $query->orderBy($sortField, $sortDirection);

        $perPage = (int) $request->get('per_page', 10);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $clients = $query->paginate($perPage)->appends($request->query());

        // Dados do plano e limites dinâmicos para exibição na view
        $tenant = $user->tenant;
        $plan = $tenant?->plan;
        $features = [];
        if ($plan && is_array($plan->features)) {
            $features = $plan->features;
        }
        $maxClients = isset($features['max_clients']) ? (int) $features['max_clients'] : 50;
        $totalClients = Client::where('tenant_id', $user->tenant_id)->count();

        return view('clients.index', compact('clients', 'plan', 'maxClients', 'totalClients'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('clients.create'), 403);
        return view('clients.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('clients.create'), 403);
        // Verificar se o usuário tem um tenant associado
        $user = auth()->user();
        if (!$user->tenant_id) {
            return redirect()->route('dashboard')->with('error', 'Usuário não possui empresa associada.');
        }
        
        // Verificar limite dinâmico conforme o plano do tenant
        $clientCount = Client::where('tenant_id', $user->tenant_id)->count();

        $tenant = $user->tenant;
        $plan = $tenant?->plan;

        // Fallback seguro caso o plano não esteja associado
        $features = [];
        if ($plan && is_array($plan->features)) {
            $features = $plan->features;
        }

        $maxClients = isset($features['max_clients']) ? (int) $features['max_clients'] : 50; // padrão

        // -1 significa ilimitado
        if ($maxClients !== -1 && $clientCount >= $maxClients) {
            $planName = $plan?->name ?? 'Atual';
            $upgradeUrl = route('plans.upgrade');
            return back()->with('error', "Limite de {$maxClients} clientes atingido no plano {$planName}. <a href='{$upgradeUrl}' class='text-blue-600 hover:text-blue-800 underline'>Faça upgrade do seu plano</a> para adicionar mais clientes.");
        }

        // Verificar limite de storage de dados antes de criar
        $estimatedSize = 4096; // ~4 KB estimado por cliente
        if (!$this->checkStorageLimit('data', $estimatedSize)) {
            return back()->withErrors([
                'storage' => $this->getStorageLimitErrorMessage('data')
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cpf_cnpj' => 'required|string|unique:clients,cpf_cnpj,NULL,id,tenant_id,' . $user->tenant_id,
            'ie_rg' => 'nullable|string|max:50',
            'type' => 'required|in:pf,pj',
            'address' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'codigo_ibge' => 'nullable|string|max:7|regex:/^[0-9]{7}$/',
            'consumidor_final' => 'required|in:S,N',
            'observations' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        // Normalizar (remover máscara) – mutators no model garantem números limpos
        // Mas garantimos aqui também para compatibilidade com unique/validação
        $validated['cpf_cnpj'] = preg_replace('/\D+/', '', (string) $validated['cpf_cnpj']);
        if (!empty($validated['phone'])) { $validated['phone'] = preg_replace('/\D+/', '', (string) $validated['phone']); }
        if (!empty($validated['zip_code'])) { $validated['zip_code'] = preg_replace('/\D+/', '', (string) $validated['zip_code']); }
        if (!empty($validated['codigo_ibge'])) { $validated['codigo_ibge'] = preg_replace('/\D+/', '', (string) $validated['codigo_ibge']); }

        // Adicionar tenant_id
        $validated['tenant_id'] = $user->tenant_id;

        try {
            $client = Client::create($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                return back()->withErrors(['cpf_cnpj' => 'Já existe um cliente com este CPF/CNPJ neste tenant.'])->withInput();
            }
            throw $e;
        }
        \App\Models\ClientAudit::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'action' => 'created',
            'changes' => [ 'name' => $client->name, 'cpf_cnpj' => $client->cpf_cnpj, 'status' => $client->status ],
        ]);
        
        // Invalidar cache de storage após criar
        $this->invalidateStorageCache();

        return redirect()->route('clients.index')->with('success', 'Cliente criado com sucesso!');
    }

    public function show(Client $client)
    {
        abort_unless(auth()->check() && $client->tenant_id === auth()->user()->tenant_id, 403);
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        abort_unless(auth()->user()->hasPermission('clients.edit'), 403);
        abort_unless(auth()->check() && $client->tenant_id === auth()->user()->tenant_id, 403);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        abort_unless(auth()->user()->hasPermission('clients.edit'), 403);
        abort_unless(auth()->check() && $client->tenant_id === auth()->user()->tenant_id, 403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'cpf_cnpj' => 'required|string|unique:clients,cpf_cnpj,' . $client->id . ',id,tenant_id,' . $client->tenant_id,
            'ie_rg' => 'nullable|string|max:50',
            'type' => 'required|in:pf,pj',
            'address' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'codigo_ibge' => 'nullable|string|max:7|regex:/^[0-9]{7}$/',
            'consumidor_final' => 'required|in:S,N',
            'observations' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        // Normalizar (remover máscara) antes de atualizar
        $validated['cpf_cnpj'] = preg_replace('/\D+/', '', (string) $validated['cpf_cnpj']);
        if (!empty($validated['phone'])) { $validated['phone'] = preg_replace('/\D+/', '', (string) $validated['phone']); }
        if (!empty($validated['zip_code'])) { $validated['zip_code'] = preg_replace('/\D+/', '', (string) $validated['zip_code']); }
        if (!empty($validated['codigo_ibge'])) { $validated['codigo_ibge'] = preg_replace('/\D+/', '', (string) $validated['codigo_ibge']); }

        $before = $client->replicate();
        $client->update($validated);
        $fields = ['name','email','phone','cpf_cnpj','type','status','address','number','complement','neighborhood','city','state','zip_code'];
        $changes = [];
        foreach ($fields as $f) { if ($before->$f != $client->$f) { $changes[$f] = ['old'=>$before->$f,'new'=>$client->$f]; } }
        if (!empty($changes)) {
            \App\Models\ClientAudit::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'client_id' => $client->id,
                'action' => 'updated',
                'changes' => $changes,
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->hasPermission('clients.delete'), 403);
        abort_unless(auth()->check() && $client->tenant_id === auth()->user()->tenant_id, 403);
        $snap = ['name' => $client->name, 'cpf_cnpj' => $client->cpf_cnpj];
        $client->delete();
        \App\Models\ClientAudit::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'client_id' => null,
            'action' => 'deleted',
            'changes' => $snap,
        ]);

        return redirect()->route('clients.index')->with('success', 'Cliente excluído com sucesso!');
    }
}