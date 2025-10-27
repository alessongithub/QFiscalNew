<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            ['name'=>'Ver PDV', 'slug'=>'pos.view'],
            ['name'=>'Criar PDV', 'slug'=>'pos.create'],
            ['name'=>'Notas de Entrada - Ver', 'slug'=>'inbound_invoices.view'],
            ['name'=>'Notas de Entrada - Criar', 'slug'=>'inbound_invoices.create'],
            ['name'=>'Notas de Entrada - Editar', 'slug'=>'inbound_invoices.edit'],
            ['name'=>'Devoluções - Ver', 'slug'=>'returns.view'],
            ['name'=>'Devoluções - Criar', 'slug'=>'returns.create'],
            ['name'=>'Configurações - Editar', 'slug'=>'settings.edit'],
            ['name'=>'Configurações Fiscais - Ver', 'slug'=>'tax_config.view'],
            ['name'=>'Configurações Fiscais - Editar', 'slug'=>'tax_config.edit'],
            ['name'=>'Tributações - Ver', 'slug'=>'tax_rates.view'],
            ['name'=>'Tributações - Criar', 'slug'=>'tax_rates.create'],
            ['name'=>'Tributações - Editar', 'slug'=>'tax_rates.edit'],
            ['name'=>'Tributações - Excluir', 'slug'=>'tax_rates.delete'],
            ['name'=>'Categorias - Ver', 'slug'=>'categories.view'],
            ['name'=>'Categorias - Criar', 'slug'=>'categories.create'],
            ['name'=>'Categorias - Editar', 'slug'=>'categories.edit'],
            ['name'=>'Categorias - Excluir', 'slug'=>'categories.delete'],
            ['name'=>'Fornecedores - Ver', 'slug'=>'suppliers.view'],
            ['name'=>'Fornecedores - Criar', 'slug'=>'suppliers.create'],
            ['name'=>'Fornecedores - Editar', 'slug'=>'suppliers.edit'],
            ['name'=>'Fornecedores - Excluir', 'slug'=>'suppliers.delete'],
            // NFe - Notas Fiscais Eletrônicas
            ['name'=>'NFe - Ver', 'slug'=>'nfe.view'],
            ['name'=>'NFe - Emitir', 'slug'=>'nfe.emit'],
            ['name'=>'NFe - Reemitir/Retry', 'slug'=>'nfe.retry'],
            ['name'=>'NFe - Cancelar', 'slug'=>'nfe.cancel'],
            // Pedidos
            ['name'=>'Pedidos - Reabrir', 'slug'=>'orders.reopen'],
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['slug'=>$p['slug']], ['name'=>$p['name']]);
        }

        // Vincular ao papel admin, se existir
        $admin = Role::where('slug','admin')->first();
        if ($admin) {
            $ids = Permission::whereIn('slug', array_column($perms,'slug'))->pluck('id')->all();
            $admin->permissions()->syncWithoutDetaching($ids);
        }
    }
}


