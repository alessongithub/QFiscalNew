@php
	$tenant = $quote->tenant;
	// Corrigir caminho da logo - buscar da tabela tenants primeiro
	$logoPath = null;
	if ($tenant && $tenant->logo_path) {
		// Se logo_path é um caminho relativo, usar asset()
		if (strpos($tenant->logo_path, 'http') === 0) {
			$logoPath = $tenant->logo_path;
		} else {
			// Verificar se arquivo existe
			if (file_exists(public_path($tenant->logo_path))) {
				$logoPath = asset($tenant->logo_path);
			} elseif (file_exists(storage_path('app/public/' . $tenant->logo_path))) {
				$logoPath = asset('storage/' . $tenant->logo_path);
			}
		}
	}
	
	if (!$logoPath) {
		// Fallbacks
		if (file_exists(public_path('logo/logo.png'))) {
			$logoPath = asset('logo/logo.png');
		} elseif (file_exists(public_path('logo_transp.png'))) {
			$logoPath = asset('logo_transp.png');
		} else {
			$logoPath = asset('logo/logo.png'); // Fallback final
		}
	}
	
	$tenantName = $tenant->fantasy_name ?? $tenant->name ?? config('app.name');
	
	// Formas de pagamento aceitas
	$paymentMethods = [];
	if ($quote->payment_methods) {
		if (is_string($quote->payment_methods)) {
			$methods = json_decode($quote->payment_methods, true);
			if (is_array($methods)) {
				$paymentMethods = $methods;
			}
		} elseif (is_array($quote->payment_methods)) {
			$paymentMethods = $quote->payment_methods;
		}
	}
	
	// Se não há métodos específicos, usar padrões
	if (empty($paymentMethods)) {
		$paymentMethods = ['Dinheiro', 'PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Transferência'];
	}
@endphp
<div style="font-family: Arial, sans-serif; background:#f9fafb; padding:24px; color:#111827;">
	<table width="100%" cellpadding="0" cellspacing="0" style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
		<tr>
			<td style="padding:16px 20px; background:#065f46; color:#ffffff;">
				<table width="100%"><tr>
					<td><strong style="font-size:18px;">{{ $tenantName }}</strong><div style="font-size:12px; opacity:.9;">Orçamento #{{ $quote->number }}</div></td>
					<td align="right"><img src="{{ $logoPath }}" alt="Logo" style="height:36px; max-width:120px;"></td>
				</tr></table>
			</td>
		</tr>
		<tr><td style="padding:20px;">
			<h2 style="margin:0 0 8px; font-size:18px;">Olá {{ optional($quote->client)->name }},</h2>
			<p style="margin:0 0 12px; font-size:14px; line-height:1.5;">
				Preparamos seu orçamento: <strong>{{ $quote->title }}</strong>.
			</p>
			
			@if($quote->validity_date)
			<div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:6px; padding:12px; margin:12px 0;">
				<strong style="color:#92400e;">📅 Validade:</strong> 
				<span style="color:#92400e;">{{ \Carbon\Carbon::parse($quote->validity_date)->format('d/m/Y') }}</span>
			</div>
			@endif
			
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
					@foreach($quote->items as $it)
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
						<td colspan="3" align="right" style="padding:10px 8px; font-weight:bold;">Total</td>
						<td align="right" style="padding:10px 8px; font-weight:bold;">R$ {{ number_format($quote->total_amount, 2, ',', '.') }}</td>
					</tr>
				</tfoot>
			</table>
			
			@if(!empty($paymentMethods))
			<div style="background:#f0f9ff; border:1px solid #0ea5e9; border-radius:6px; padding:12px; margin:12px 0;">
				<strong style="color:#0c4a6e;">💳 Formas de Pagamento Aceitas:</strong>
				<div style="margin-top:6px; color:#0c4a6e;">
					@foreach($paymentMethods as $method)
						<span style="display:inline-block; background:#e0f2fe; color:#0c4a6e; padding:4px 8px; border-radius:4px; margin:2px; font-size:12px;">{{ $method }}</span>
					@endforeach
				</div>
			</div>
			@endif
			
			@if(!empty($quote->notes))
				<div style="margin-top:8px; font-size:12px; color:#374151;">
					<strong>Observações:</strong> {{ $quote->notes }}
				</div>
			@endif
			
			<p style="margin-top:16px; font-size:13px; color:#374151;">
				Qualquer dúvida, estamos à disposição.
			</p>
			
			<div style="margin-top:16px; font-size:12px; color:#6b7280;">
				Atenciosamente,<br>
				{{ $tenantName }}
			</div>
		</td></tr>
	</table>
</div>


