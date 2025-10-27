<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function tenantPolicy(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 401);
        $tenant = $user->tenant;
        abort_unless($tenant, 403);
        $plan = $tenant->plan;

        $features = [];
        if ($plan) {
            $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
        }

        // Derivar política com defaults sensatos
        $hasErp = (bool) ($features['has_erp'] ?? true);
        $catalogSource = (string) ($features['catalog_source'] ?? ($hasErp ? 'erp' : 'emissor'));
        if (!in_array($catalogSource, ['erp','emissor'], true)) {
            $catalogSource = 'erp';
        }
        $allowLocalEdits = (bool) ($features['allow_local_catalog_edits'] ?? ($catalogSource === 'emissor'));
        $allowIssueNfe = (bool) ($features['allow_issue_nfe'] ?? ($features['has_emissor'] ?? false));
        $allowPos = (bool) ($features['allow_pos'] ?? ($features['has_pos'] ?? false));

        return response()->json([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan?->id,
            'policy' => [
                'catalog_source' => $catalogSource,
                'allow_local_catalog_edits' => $allowLocalEdits,
                'allow_issue_nfe' => $allowIssueNfe,
                'allow_pos' => $allowPos,
            ],
            'features' => $features,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}


