<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name'=>'Gestor','slug'=>'manager'],
            ['name'=>'Operador','slug'=>'operator'],
            ['name'=>'Técnico','slug'=>'technician'],
        ];
        foreach ($roles as $r) { Role::firstOrCreate(['slug'=>$r['slug']], ['name'=>$r['name']]); }

        $perm = fn($slug)=> Permission::where('slug',$slug)->first()?->id;

        $managerPerms = array_filter([
            $perm('pos.view'), $perm('pos.create'),
            $perm('inbound_invoices.view'), $perm('inbound_invoices.create'), $perm('inbound_invoices.edit'),
            $perm('returns.view'), $perm('returns.create'),
            $perm('categories.view'), $perm('categories.create'), $perm('categories.edit'), $perm('categories.delete'),
            $perm('suppliers.view'), $perm('suppliers.create'), $perm('suppliers.edit'), $perm('suppliers.delete'),
            $perm('settings.edit'),
            // NFe completa para gestor
            $perm('nfe.view'), $perm('nfe.emit'), $perm('nfe.retry'), $perm('nfe.cancel'),
        ]);

        $operatorPerms = array_filter([
            $perm('pos.view'), $perm('pos.create'),
            $perm('returns.view'), $perm('returns.create'),
            $perm('inbound_invoices.view'), $perm('inbound_invoices.create'),
            $perm('categories.view'), $perm('suppliers.view'),
            // Operador pode visualizar e emitir NFe
            $perm('nfe.view'), $perm('nfe.emit'),
        ]);

        $technicianPerms = array_filter([
            // foco em OS e calendário (já existentes no sistema)
            $perm('calendar.view'),
            // Sem financeiro e sem PDV por padrão
            // Por padrão, técnico não emite NFe
        ]);

        $manager = Role::where('slug','manager')->first();
        $operator = Role::where('slug','operator')->first();
        $technician = Role::where('slug','technician')->first();

        if ($manager) { $manager->permissions()->syncWithoutDetaching($managerPerms); }
        if ($operator) { $operator->permissions()->syncWithoutDetaching($operatorPerms); }
        if ($technician) { $technician->permissions()->syncWithoutDetaching($technicianPerms); }
    }
}


