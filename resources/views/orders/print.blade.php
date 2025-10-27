<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $order->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; }
        .container { max-width: 900px; margin: 0 auto; padding: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; font-size: 14px; text-align: left; }
        .right { text-align: right; }
        .total { margin-top: 12px; border: 1px solid #ddd; padding: 8px; width: 340px; margin-left: auto; }
        .muted { color: #555; font-size: 12px; }
        .title { font-size: 20px; font-weight: bold; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div class="title">Pedido #{{ $order->number }}</div>
                <div class="muted">{{ $order->title }}</div>
                <div class="muted">Data: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            @php
                $tenant = optional($order->tenant);
                $logoPath = $tenant && $tenant->logo_path ? $tenant->logo_path : null;
                $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : asset('logo.png');
            @endphp
            <img src="{{ $logoUrl }}" style="height:40px;"/>
        </div>

        <div style="margin-top:12px; display:flex; gap:24px;">
            <div style="flex:1;">
                <div style="font-weight:600;">Cliente</div>
                <div>{{ optional($order->client)->name }}</div>
            </div>
            <div style="flex:1;">
                <div style="font-weight:600;">Status</div>
                <div>{{ $order->status }}</div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Item</th><th>Qtd</th><th>UN</th><th class="right">V.Unit</th><th class="right">Desc.</th><th class="right">Acrésc.</th><th class="right">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($order->items as $it)
                <tr>
                    <td>
                        <div>{{ $it->name }}</div>
                        @if($it->description)<div class="muted">{{ $it->description }}</div>@endif
                    </td>
                    <td>{{ number_format((float)$it->quantity, 3, ',', '.') }}</td>
                    <td>{{ $it->unit }}</td>
                    <td class="right">R$ {{ number_format((float)$it->unit_price, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)($it->discount_value ?? 0), 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)($it->addition_value ?? 0), 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)$it->line_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="total">
            <div style="display:flex; justify-content:space-between;"><span>Descontos</span><span>R$ {{ number_format((float)($order->discount_total ?? 0), 2, ',', '.') }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Acréscimos</span><span>R$ {{ number_format((float)($order->addition_total ?? 0), 2, ',', '.') }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Frete</span><span>R$ {{ number_format((float)($order->freight_cost ?? 0), 2, ',', '.') }}</span></div>
            <div style="display:flex; justify-content:space-between; font-weight:600;">
                <span>Total</span><span>R$ {{ number_format((float)$order->total_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        @if(isset($taxEstimate))
        <div class="muted" style="margin-top:6px; font-size:11px;">
            Estimativa de tributos (não oficial): ICMS R$ {{ number_format((float)($taxEstimate['icms'] ?? 0), 2, ',', '.') }},
            PIS R$ {{ number_format((float)($taxEstimate['pis'] ?? 0), 2, ',', '.') }},
            COFINS R$ {{ number_format((float)($taxEstimate['cofins'] ?? 0), 2, ',', '.') }}.
        </div>
        @endif

        @if(isset($icmsSuggestions) && is_array($icmsSuggestions) && count($icmsSuggestions) > 0)
        <div class="muted" style="margin-top:6px; font-size:11px; background:#fff7ed; border:1px dashed #f59e0b; padding:8px;">
            <div style="font-weight:600; color:#9a6700;">Sugestões de ICMS (créditos fiscais encontrados)</div>
            <ul style="margin-left:16px; list-style:disc;">
                @foreach($icmsSuggestions as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div style="margin-top:16px; border:1px solid #ddd; padding:12px;">
            <div style="font-weight:600; margin-bottom:8px;">Informações de Transporte</div>
            <table>
                <tbody>
                <tr>
                    <td style="width:40%">Quantidade de Volumes</td>
                    <td>{{ (int)($order->volume_qtd ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Espécie de Volume</td>
                    <td>{{ $order->volume_especie ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Peso Bruto Total (kg)</td>
                    <td>{{ number_format((float)($order->peso_bruto ?? 0), 3, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Peso Líquido Total (kg)</td>
                    <td>{{ number_format((float)($order->peso_liquido ?? 0), 3, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Valor do Seguro (R$)</td>
                    <td>R$ {{ number_format((float)($order->valor_seguro ?? 0), 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Outras Despesas Acessórias (R$)</td>
                    <td>R$ {{ number_format((float)($order->outras_despesas ?? 0), 2, ',', '.') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        @if(isset($rateioItems) && count($rateioItems) > 0)
        <div style="margin-top:16px; border:1px solid #ddd; padding:12px;">
            <div style="font-weight:600; margin-bottom:8px;">Rateio por Item (para conferência)</div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="right">vDesc</th>
                        <th class="right">vFrete</th>
                        <th class="right">vSeg</th>
                        <th class="right">vOutro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rateioItems as $ri)
                        <tr>
                            <td>{{ $ri['name'] }}</td>
                            <td class="right">R$ {{ number_format((float)($ri['vDesc'] ?? 0), 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)($ri['vFrete'] ?? 0), 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)($ri['vSeg'] ?? 0), 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format((float)($ri['vOutro'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="muted" style="margin-top:6px; font-size:11px;">Exibição opcional destinada à conferência interna. Valores distribuídos proporcionalmente ao valor líquido do item.</div>
        </div>
        @endif

        @if(isset($receivables) && $receivables->count() > 0)
        <div style="margin-top:16px; border:1px solid #ddd; padding:12px;">
            <div style="font-weight:600; margin-bottom:8px;">Formas de Pagamento (Parcelas)</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vencimento</th>
                        <th>Meio de Pagamento</th>
                        <th>tPag</th>
                        <th class="right">Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $idx=0; @endphp
                    @foreach($receivables as $r)
                        @php $idx++; @endphp
                        <tr>
                            <td>{{ $idx }}</td>
                            <td>{{ optional($r->due_date)->format('d/m/Y') }}</td>
                            <td>
                                {{ strtoupper($r->payment_method ?? '-') }}
                                @if(!empty($r->tpag_hint))
                                    <span class="muted">({{ $r->tpag_hint }})</span>
                                @endif
                            </td>
                            <td>{{ $r->tpag_override ?: '-' }}</td>
                            <td class="right">R$ {{ number_format((float)$r->amount, 2, ',', '.') }}</td>
                            <td>{{ ['open'=>'Em aberto','partial'=>'Parcial','paid'=>'Pago','canceled'=>'Cancelado'][$r->status] ?? $r->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="margin-top:16px; border:1px solid #ddd; padding:12px;">
            <div style="font-weight:600; margin-bottom:8px;">Observações Fiscais</div>
            <table>
                <tbody>
                    <tr>
                        <td style="width:40%">Informações complementares (infCpl)</td>
                        <td>{{ $order->additional_info ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td>Informações ao Fisco (infAdFisco)</td>
                        <td>{{ $order->fiscal_info ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="muted" style="margin-top:6px; font-size:11px;">
                Observações Fiscais refletem o conteúdo preparado para a NF-e.
            </div>
        </div>

        <div class="no-print" style="margin-top:12px;">
            <div class="muted" style="font-size:10px; margin-bottom:6px;">Pedido emitido por QFiscal www.qfiscal.com.br</div>
            <button onclick="window.print()">Imprimir</button>
        </div>
        <div class="muted" style="margin-top:10px; font-size:10px; color:#666;">
            Tributos estimados — cálculo não oficial. Os valores definitivos constam no XML autorizado pela SEFAZ.
        </div>
    </div>
</body>
</html>


