<div style="font-family: Arial, sans-serif; font-size:14px; color:#222;">
    <p>Olá {{ optional($receivable->client)->name ?: 'cliente' }},</p>
    <p>Segue o link para pagamento do seu boleto referente a: <strong>{{ $receivable->description }}</strong>.</p>
    <p>
        <a href="{{ $link }}" target="_blank" style="background:#6b21a8;color:#fff;padding:10px 14px;text-decoration:none;border-radius:6px;">Visualizar boleto</a>
    </p>
    @if(!empty($receivable->boleto_barcode))
        <p>Linha digitável / Código de barras:<br>
        <span style="font-family: monospace;">{{ $receivable->boleto_barcode }}</span></p>
    @endif
    <p>Vencimento: <strong>{{ \Carbon\Carbon::parse($receivable->due_date)->format('d/m/Y') }}</strong></p>
    <p>Valor: <strong>R$ {{ number_format((float)$receivable->amount,2,',','.') }}</strong></p>
    <p>Qualquer dúvida estamos à disposição.</p>
    <p>Atenciosamente,<br>{{ auth()->user()->tenant->trade_name ?? config('app.name') }}</p>
</div>


