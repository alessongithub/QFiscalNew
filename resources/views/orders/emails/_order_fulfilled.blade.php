@php
    $tenant = $order->tenant;
    $logoUrl = ($tenant && $tenant->logo_path) ? (Storage::disk('public')->url($tenant->logo_path)) : asset('logo_transp.png');
    $tenantName = $tenant->fantasy_name ?? $tenant->name ?? config('app.name');
    $items = $order->items ?? collect();
    $total = (float)($order->total_amount ?? 0);
@endphp
<div style="font-family: Arial, sans-serif; background:#f9fafb; padding:24px; color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="padding:16px 20px; background:#065f46; color:#ffffff;">
                <table width="100%"><tr>
                    <td><strong style="font-size:18px;">{{ $tenantName }}</strong><div style="font-size:12px; opacity:.9;">Pedido #{{ $order->number }}</div></td>
                    <td align="right"><img src="{{ $logoUrl }}" alt="Logo" style="height:36px"></td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding:20px;">
            <h2 style="margin:0 0 8px; font-size:18px;">Seu pedido foi finalizado!</h2>
            <p style="margin:0 0 12px; font-size:14px;">Pedido <strong>#{{ $order->number }}</strong> — {{ $order->title }}</p>
            <p style="margin:0 0 12px; font-size:14px;">Total: <strong>R$ {{ number_format($total,2,',','.') }}</strong></p>
            <p style="margin:0; font-size:13px; color:#374151;">Agradecemos a preferência. Caso exista retirada, você será informado do envio.</p>
            <div style="margin-top:16px; font-size:12px; color:#6b7280;">Atenciosamente,<br>{{ $tenantName }}</div>
        </td></tr>
    </table>
</div>


