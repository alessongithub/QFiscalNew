@php
    $tenant = $order->tenant;
    $logoUrl = ($tenant && $tenant->logo_path) ? (Storage::disk('public')->url($tenant->logo_path)) : asset('logo_transp.png');
    $tenantName = $tenant->fantasy_name ?? $tenant->name ?? config('app.name');
    $items = $order->items ?? collect();
    $discount = (float)($order->discount_total ?? 0);
    $addition = (float)($order->addition_total ?? 0);
    $subtotal = (float)($items?->sum('line_total') ?? $order->total_amount ?? 0);
    $total = max(0, $subtotal - $discount + $addition);
    $carrier = $order->carrier;
@endphp
<div style="font-family: Arial, sans-serif; background:#f9fafb; padding:24px; color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="padding:16px 20px; background:#1f2937; color:#ffffff;">
                <table width="100%"><tr>
                    <td><strong style="font-size:18px;">{{ $tenantName }}</strong><div style="font-size:12px; opacity:.9;">Pedido #{{ $order->number }}</div></td>
                    <td align="right"><img src="{{ $logoUrl }}" alt="Logo" style="height:36px"></td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding:20px;">
            <h2 style="margin:0 0 8px; font-size:18px;">Olá {{ optional($order->client)->name }},</h2>
            <p style="margin:0 0 12px; font-size:14px; line-height:1.5;">
                Confirmamos o recebimento do seu pedido <strong>#{{ $order->number }}</strong> — {{ $order->title }}.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0; font-size:13px;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th align="left" style="padding:8px;">Item</th>
                        <th align="center" style="padding:8px;">Qtde</th>
                        <th align="right" style="padding:8px;">Unitário</th>
                        <th align="right" style="padding:8px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $it)
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #eee;">{{ $it->name }}</td>
                        <td align="center" style="padding:8px; border-bottom:1px solid #eee;">{{ (float)$it->quantity }} {{ $it->unit }}</td>
                        <td align="right" style="padding:8px; border-bottom:1px solid #eee;">R$ {{ number_format($it->unit_price, 2, ',', '.') }}</td>
                        <td align="right" style="padding:8px; border-bottom:1px solid #eee;">R$ {{ number_format($it->line_total, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" align="right" style="padding:10px 8px;">Subtotal</td>
                        <td align="right" style="padding:10px 8px;">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @if($discount>0)
                    <tr>
                        <td colspan="3" align="right" style="padding:4px 8px;">Descontos</td>
                        <td align="right" style="padding:4px 8px;">- R$ {{ number_format($discount, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($addition>0)
                    <tr>
                        <td colspan="3" align="right" style="padding:4px 8px;">Acréscimos</td>
                        <td align="right" style="padding:4px 8px;">+ R$ {{ number_format($addition, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" align="right" style="padding:10px 8px; font-weight:bold;">Total</td>
                        <td align="right" style="padding:10px 8px; font-weight:bold;">R$ {{ number_format($total, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
            <div style="margin-top:12px; font-size:13px; color:#374151;">
                <div><strong>Forma de pagamento:</strong> {{ $order->payment_method ?? 'A definir' }}</div>
                @if(!empty($order->payment_terms))
                <div><strong>Condições:</strong> {{ $order->payment_terms }}</div>
                @endif
                @if($carrier)
                <div><strong>Frete:</strong> {{ $carrier->name }} @if(!empty($order->freight_cost)) — R$ {{ number_format($order->freight_cost,2,',','.') }} @endif</div>
                @endif
            </div>
            <p style="margin-top:16px; font-size:13px; color:#374151;">Qualquer dúvida, estamos à disposição.</p>
            <div style="margin-top:16px; font-size:12px; color:#6b7280;">Atenciosamente,<br>{{ $tenantName }}</div>
        </td></tr>
    </table>
</div>


