@php
    $tenant = $serviceOrder->tenant;
    // Verificar se existe logo_path e se o arquivo existe
    $logoUrl = asset('logo.png'); // Default
    if ($tenant && $tenant->logo_path) {
        $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($tenant->logo_path);
        if (file_exists($logoPath)) {
            $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($tenant->logo_path);
            // Garantir URL absoluta
            if (!str_starts_with($logoUrl, 'http')) {
                $logoUrl = url($logoUrl);
            }
        }
    }
    $tenantName = $tenant->fantasy_name ?? $tenant->name ?? config('app.name');
@endphp
<div style="font-family: Arial, sans-serif; font-size:14px; color:#111; background:#f8fafc; padding:20px;">
    <div style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="display:flex; align-items:center; gap:12px; padding:16px 20px; background:#111827; color:#fff;">
            <img src="{{ $logoUrl }}" alt="Logo" style="height:36px;" />
            <div style="font-weight:600; font-size:16px;">{{ $tenantName }}</div>
        </div>
        <div style="padding:20px;">
            <p style="margin:0 0 10px 0;">Olá <strong>{{ $client->name }}</strong>,</p>
            <p style="margin:0 0 14px 0;">Sua Ordem de Serviço <strong>#{{ $serviceOrder->number }}</strong> - "{{ $serviceOrder->title }}" foi finalizada e está pronta para retirada.</p>
            <div style="margin-top:12px;">
                <div style="font-weight:600; margin-bottom:8px;">Resumo</div>
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <tbody>
                        <tr>
                            <td style="padding:8px; color:#6b7280;">Total</td>
                            <td style="padding:8px; text-align:right; font-weight:600;">R$ {{ number_format((float)($serviceOrder->total_amount ?? 0),2,',','.') }}</td>
                        </tr>
                        @if(!empty($serviceOrder->warranty_until))
                        <tr>
                            <td style="padding:8px; color:#6b7280;">Garantia até</td>
                            <td style="padding:8px; text-align:right;">{{ \Carbon\Carbon::parse($serviceOrder->warranty_until)->format('d/m/Y') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <p style="margin-top:18px;">Por favor, dirija-se à nossa unidade para retirada. Qualquer dúvida, responda este e-mail.</p>
            <p style="margin-top:18px; color:#6b7280; font-size:12px;">Este e-mail foi enviado pela {{ $tenantName }} via QFiscal.</p>
        </div>
    </div>
</div>


