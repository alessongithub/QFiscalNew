<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanUpgradeController extends Controller
{
    public function showUpgrade()
    {
        $plans = Plan::where('active', true)->get();
        $currentTenant = auth()->user()->tenant;
        $currentPlan = $currentTenant?->plan;
        $currentPlanId = (int) ($currentTenant?->plan_id ?? 0);
        $currentPlanSlug = $currentPlan?->slug ?? null;

        \Log::info('PlanUpgrade Debug', [
            'tenant_id' => $currentTenant?->id,
            'current_plan_id' => $currentPlanId,
            'current_plan_slug' => $currentPlanSlug,
            'current_plan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'slug' => $currentPlan->slug,
            ] : null,
            'available_plans' => $plans->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
            ])->toArray()
        ]);

        return view('plans.upgrade', compact('plans', 'currentPlan', 'currentPlanId', 'currentPlanSlug'));
    }

    public function processUpgrade(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = auth()->user()->tenant;
        $newPlan = Plan::where('active', true)->findOrFail($validated['plan_id']);

        \Log::info('PlanUpgrade Process Debug', [
            'tenant_id' => $tenant->id,
            'tenant_plan_id' => $tenant->plan_id,
            'new_plan_id' => $newPlan->id,
            'tenant_plan' => $tenant->plan ? [
                'id' => $tenant->plan->id,
                'name' => $tenant->plan->name,
                'slug' => $tenant->plan->slug,
            ] : null,
            'new_plan' => [
                'id' => $newPlan->id,
                'name' => $newPlan->name,
                'slug' => $newPlan->slug,
            ]
        ]);

        // Impedir seleção do mesmo plano atual (por id ou por slug), exceto se estiver vencido (leva para renovar)
        $isSameById = ((int) $tenant->plan_id === (int) $newPlan->id);
        $tenantPlan = $tenant->plan; // pode estar inativo
        $isSameBySlug = false;
        
        if ($tenantPlan && $tenantPlan->slug && $newPlan->slug) {
            $isSameBySlug = (strtolower($tenantPlan->slug) === strtolower($newPlan->slug));
        }

        \Log::info('PlanUpgrade Process Debug', [
            'tenant_plan_id' => $tenant->plan_id,
            'new_plan_id' => $newPlan->id,
            'is_same_by_id' => $isSameById,
            'tenant_plan_slug' => $tenantPlan?->slug,
            'new_plan_slug' => $newPlan->slug,
            'is_same_by_slug' => $isSameBySlug
        ]);

        if ($isSameById || $isSameBySlug) {
            if ($tenant->plan_expires_at && $tenant->plan_expires_at->isPast()) {
                return redirect()->route('checkout.index', ['plan_id' => $newPlan->id]);
            }
            return back()->with('error', 'Você já está no plano <strong>' . e($newPlan->name) . '</strong>.');
        }

        // Se plano gratuito, troca direto sem checkout
        if ((string) $newPlan->price === '0.00') {
            $tenant->update([
                'plan_id' => $newPlan->id,
                'plan_expires_at' => null,
            ]);
            return redirect()->route('dashboard')->with('success', 'Migrado para o plano gratuito.');
        }

        // Para planos pagos, redirecionar para checkout
        return redirect()->route('checkout.index', ['plan_id' => $newPlan->id]);
    }
}


