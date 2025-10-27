<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('suppliers.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = Supplier::where('tenant_id', $tenantId);
        if ($s = trim((string)$request->get('search', ''))) {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('trade_name', 'like', "%{$s}%")
                   ->orWhere('cpf_cnpj', 'like', "%{$s}%")
                   ->orWhere('email', 'like', "%{$s}%");
            });
        }
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $suppliers = $q->orderBy('name')->paginate($perPage)->appends($request->query());
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('suppliers.create'), 403);
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('suppliers.create'), 403);
        
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20',
            'ie_rg' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'required|string|max:255',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'required|string|max:20',
            'active' => 'nullable|boolean',
        ], [
            'name.required' => 'O nome do fornecedor é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'trade_name.required' => 'O nome fantasia é obrigatório.',
            'trade_name.max' => 'O nome fantasia não pode ter mais de 255 caracteres.',
            'cpf_cnpj.required' => 'O CPF/CNPJ é obrigatório.',
            'cpf_cnpj.max' => 'O CPF/CNPJ não pode ter mais de 20 caracteres.',
            'email.email' => 'Digite um e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 150 caracteres.',
            'address.required' => 'O endereço é obrigatório.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'zip_code.required' => 'O CEP é obrigatório.',
            'zip_code.max' => 'O CEP não pode ter mais de 20 caracteres.',
            'state.max' => 'A UF deve ter no máximo 2 caracteres.',
        ]);
        
        // Validar CPF/CNPJ
        $cpfCnpjDigits = preg_replace('/[^0-9]/', '', $v['cpf_cnpj']);
        if (!$this->validateCpfCnpj($cpfCnpjDigits)) {
            return back()->withErrors(['cpf_cnpj' => 'CPF/CNPJ inválido.'])->withInput();
        }
        
        // Validar número do endereço
        if (empty($v['number']) || trim($v['number']) === '') {
            $v['number'] = 'S/N';
        }
        
        $v['tenant_id'] = auth()->user()->tenant_id;
        $v['active'] = $request->boolean('active', true);
        
        Supplier::create($v);
        return redirect()->route('suppliers.index')->with('success', 'Fornecedor criado com sucesso.');
    }

    public function edit(Supplier $supplier)
    {
        abort_unless(auth()->user()->hasPermission('suppliers.edit'), 403);
        abort_unless($supplier->tenant_id === auth()->user()->tenant_id, 403);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless(auth()->user()->hasPermission('suppliers.edit'), 403);
        abort_unless($supplier->tenant_id === auth()->user()->tenant_id, 403);
        
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20',
            'ie_rg' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'required|string|max:255',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'required|string|max:20',
            'active' => 'nullable|boolean',
        ], [
            'name.required' => 'O nome do fornecedor é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'trade_name.required' => 'O nome fantasia é obrigatório.',
            'trade_name.max' => 'O nome fantasia não pode ter mais de 255 caracteres.',
            'cpf_cnpj.required' => 'O CPF/CNPJ é obrigatório.',
            'cpf_cnpj.max' => 'O CPF/CNPJ não pode ter mais de 20 caracteres.',
            'email.email' => 'Digite um e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 150 caracteres.',
            'address.required' => 'O endereço é obrigatório.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'zip_code.required' => 'O CEP é obrigatório.',
            'zip_code.max' => 'O CEP não pode ter mais de 20 caracteres.',
            'state.max' => 'A UF deve ter no máximo 2 caracteres.',
        ]);
        
        // Validar CPF/CNPJ
        $cpfCnpjDigits = preg_replace('/[^0-9]/', '', $v['cpf_cnpj']);
        if (!$this->validateCpfCnpj($cpfCnpjDigits)) {
            return back()->withErrors(['cpf_cnpj' => 'CPF/CNPJ inválido.'])->withInput();
        }
        
        // Validar número do endereço
        if (empty($v['number']) || trim($v['number']) === '') {
            $v['number'] = 'S/N';
        }
        
        $v['active'] = $request->boolean('active', true);
        $supplier->update($v);
        return redirect()->route('suppliers.index')->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Supplier $supplier)
    {
        abort_unless(auth()->user()->hasPermission('suppliers.delete'), 403);
        abort_unless($supplier->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se o fornecedor está sendo usado em produtos
        $productsCount = \App\Models\Product::where('supplier_id', $supplier->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->count();
            
        // Verificar se o fornecedor está sendo usado em contas a pagar
        $payablesCount = \App\Models\Payable::where('supplier_id', $supplier->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->count();
            
        // Verificar se o fornecedor está sendo usado em notas fiscais de entrada
        $inboundInvoicesCount = \App\Models\InboundInvoice::where('supplier_id', $supplier->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->count();
        
        // Se o fornecedor está sendo usado em algum lugar, apenas desativar
        if ($productsCount > 0 || $payablesCount > 0 || $inboundInvoicesCount > 0) {
            $supplier->update(['active' => false]);
            
            $message = 'Fornecedor desativado com sucesso. ';
            if ($productsCount > 0) {
                $message .= "Está sendo usado em {$productsCount} produto(s). ";
            }
            if ($payablesCount > 0) {
                $message .= "Possui {$payablesCount} conta(s) a pagar. ";
            }
            if ($inboundInvoicesCount > 0) {
                $message .= "Possui {$inboundInvoicesCount} nota(s) fiscal(is) de entrada. ";
            }
            $message .= 'Para excluir definitivamente, remova todas as referências primeiro.';
            
            return redirect()->route('suppliers.index')->with('warning', $message);
        }
        
        // Se não está sendo usado, permitir exclusão
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Fornecedor excluído com sucesso.');
    }
    
    /**
     * Valida CPF ou CNPJ
     */
    private function validateCpfCnpj($document)
    {
        $document = preg_replace('/[^0-9]/', '', $document);
        
        if (strlen($document) === 11) {
            return $this->validateCpf($document);
        } elseif (strlen($document) === 14) {
            return $this->validateCnpj($document);
        }
        
        return false;
    }
    
    /**
     * Valida CPF
     */
    private function validateCpf($cpf)
    {
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if (intval($cpf[9]) !== $digit1) {
            return false;
        }
        
        // Calcula o segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return intval($cpf[10]) === $digit2;
    }
    
    /**
     * Valida CNPJ
     */
    private function validateCnpj($cnpj)
    {
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Calcula o primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if (intval($cnpj[12]) !== $digit1) {
            return false;
        }
        
        // Calcula o segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return intval($cnpj[13]) === $digit2;
    }
}


