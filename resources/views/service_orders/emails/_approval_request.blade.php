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
    $items = $serviceOrder->items ?? collect();
    $hasItems = $items && $items->count() > 0;
    $discount = (float)($serviceOrder->discount_total ?? 0);
    $addition = (float)($serviceOrder->addition_total ?? 0);
    $subtotal = (float)($items?->sum('line_total') ?? $serviceOrder->budget_amount ?? 0);
    $total = max(0, $subtotal - $discount + $addition);
@endphp
<div style="font-family: Arial, sans-serif; font-size:14px; color:#111; background:#f8fafc; padding:20px;">
    <div style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="display:flex; align-items:center; gap:12px; padding:16px 20px; background:#111827; color:#fff;">
            <img src="{{ $logoUrl }}" alt="Logo" style="height:36px;" />
            <div style="font-weight:600; font-size:16px;">{{ $tenantName }}</div>
        </div>
        <div style="padding:20px;">
            <p style="margin:0 0 10px 0;">Olá <strong>{{ $client->name }}</strong>,</p>
            <p style="margin:0 0 14px 0;">Enviamos o orçamento da sua Ordem de Serviço <strong>#{{ $serviceOrder->number }}</strong> - "{{ $serviceOrder->title }}" para sua análise.</p>
            @if(!empty($serviceOrder->diagnosis))
                <div style="margin:14px 0; padding:12px; background:#f3f4f6; border-radius:8px;">
                    <div style="font-weight:600; margin-bottom:6px;">Diagnóstico</div>
                    <div>{{ $serviceOrder->diagnosis }}</div>
                </div>
            @endif

            @if($hasItems)
                <div style="margin-top:12px;">
                    <div style="font-weight:600; margin-bottom:8px;">Itens do orçamento</div>
                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr>
                                <th style="text-align:left; border-bottom:1px solid #e5e7eb; padding:8px;">Item</th>
                                <th style="text-align:center; border-bottom:1px solid #e5e7eb; padding:8px;">Qtde</th>
                                <th style="text-align:right; border-bottom:1px solid #e5e7eb; padding:8px;">V.Unit</th>
                                <th style="text-align:right; border-bottom:1px solid #e5e7eb; padding:8px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $it)
                                <tr>
                                    <td style="border-bottom:1px solid #f1f5f9; padding:8px;">
                                        <div style="font-weight:600;">{{ $it->name }}</div>
                                        @if(!empty($it->description))
                                            <div style="color:#6b7280;">{{ $it->description }}</div>
                                        @endif
                                    </td>
                                    <td style="border-bottom:1px solid #f1f5f9; padding:8px; text-align:center;">{{ (float)$it->quantity }}</td>
                                    <td style="border-bottom:1px solid #f1f5f9; padding:8px; text-align:right;">R$ {{ number_format((float)$it->unit_price,2,',','.') }}</td>
                                    <td style="border-bottom:1px solid #f1f5f9; padding:8px; text-align:right;">R$ {{ number_format((float)$it->line_total,2,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:14px; display:flex; justify-content:flex-end;">
                <div style="width:320px; border:1px solid #e5e7eb; border-radius:8px; padding:12px; background:#f9fafb;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <div style="color:#6b7280;">Subtotal</div>
                        <div>R$ {{ number_format($subtotal,2,',','.') }}</div>
                    </div>
                    @if($discount > 0)
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <div style="color:#6b7280;">Descontos</div>
                            <div>- R$ {{ number_format($discount,2,',','.') }}</div>
                        </div>
                    @endif
                    @if($addition > 0)
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <div style="color:#6b7280;">Acréscimos</div>
                            <div>+ R$ {{ number_format($addition,2,',','.') }}</div>
                        </div>
                    @endif
                    <div style="display:flex; justify-content:space-between; font-weight:700; font-size:15px; padding-top:8px; border-top:1px solid #e5e7eb;">
                        <div>Total</div>
                        <div>R$ {{ number_format($total,2,',','.') }}</div>
                    </div>
                </div>
            </div>

            <div style="margin-top:18px;">
                <div style="margin-bottom:8px;">Para prosseguir, escolha uma opção:</div>
                <div>
                    <a href="{{ $approveUrl ?? '#' }}" style="display:inline-block;margin-right:8px;padding:10px 16px;background:#16a34a;color:#fff;text-decoration:none;border-radius:6px">Aprovar</a>
                    <a href="{{ $rejectUrl ?? '#' }}" style="display:inline-block;padding:10px 16px;background:#dc2626;color:#fff;text-decoration:none;border-radius:6px">Não Aprovar</a>
                </div>
            </div>

            <p style="margin-top:18px; color:#6b7280; font-size:12px;">Este e-mail foi enviado pela {{ $tenantName }} via QFiscal. Em caso de dúvidas, responda este e-mail.</p>
        </div>
    </div>
</div>


