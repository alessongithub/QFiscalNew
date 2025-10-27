<x-app-layout>
    <style>
        @media print {
            @page { 
                size: A4; 
                margin: 8mm; 
            }
            html, body { 
                background: #ffffff !important; 
                font-size: 12px !important;
            }
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area { 
                position: absolute !important; 
                left: 0; 
                top: 0; 
                width: 100% !important; 
                margin: 0 !important; 
                page-break-inside: avoid !important;
            }
            #print-content { 
                padding: 8mm !important; 
                font-size: 12px !important;
            }
            .receipt-container {
                background: white !important;
                padding: 0 !important;
            }
            .receipt-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                max-width: none !important;
            }
            .receipt-header {
                background: white !important;
                color: black !important;
                padding: 1rem !important;
                border-bottom: 2px solid #000 !important;
            }
            .receipt-header::before {
                display: none !important;
            }
            .receipt-title {
                font-size: 1.5rem !important;
                color: black !important;
            }
            .receipt-subtitle {
                color: black !important;
            }
            .receipt-body {
                padding: 1rem !important;
            }
            .receipt-section {
                background: white !important;
                border: 1px solid #000 !important;
                border-left: 3px solid #000 !important;
                margin-bottom: 1rem !important;
                padding: 0.8rem !important;
            }
            .receipt-section-title {
                color: black !important;
                font-size: 1rem !important;
            }
            .receipt-section-title::before {
                content: '•' !important;
                color: black !important;
            }
            .receipt-info-item {
                border-bottom: 1px solid #ccc !important;
                padding: 0.4rem 0 !important;
            }
            .receipt-label {
                color: black !important;
            }
            .receipt-value {
                color: black !important;
            }
            .receipt-amount {
                color: black !important;
                font-size: 1.2rem !important;
            }
            .receipt-footer {
                margin-top: 1.5rem !important;
                padding-top: 1rem !important;
                border-top: 2px solid #000 !important;
            }
            .receipt-signature-line {
                border-bottom: 2px solid #000 !important;
            }
            .receipt-signature-text {
                color: black !important;
            }
            .receipt-company {
                color: black !important;
            }
            .receipt-company-name {
                color: black !important;
            }
            .no-print { display: none !important; }
        }
        
        .receipt-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .receipt-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .receipt-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .receipt-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .receipt-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .receipt-body {
            padding: 2.5rem;
        }
        
        .receipt-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .receipt-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .receipt-section-title::before {
            content: '💰';
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
        
        .receipt-info {
            display: grid;
            gap: 1rem;
        }
        
        .receipt-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .receipt-info-item:last-child {
            border-bottom: none;
        }
        
        .receipt-label {
            font-weight: 500;
            color: #6b7280;
        }
        
        .receipt-value {
            font-weight: 600;
            color: #111827;
        }
        
        .receipt-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .receipt-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .receipt-signature {
            text-align: center;
        }
        
        .receipt-signature-line {
            border-bottom: 2px solid #374151;
            width: 200px;
            margin: 0 auto 0.5rem;
            height: 40px;
        }
        
        .receipt-signature-text {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .receipt-disclaimer {
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
            font-style: italic;
        }
        
        .receipt-company {
            text-align: right;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .receipt-company-name {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .print-button {
            background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        @media print {
            .receipt-container {
                background: white !important;
                padding: 0 !important;
            }
            
            .receipt-card {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
    
    <div class="receipt-container">
        <div id="print-area" class="receipt-card">
            <div id="print-content">
                <!-- Header -->
                <div class="receipt-header">
                    <div class="receipt-title">RECIBO</div>
                    <div class="receipt-subtitle">Nº {{ $receipt->number }}</div>
                </div>
                
                <!-- Print Button -->
                <div class="no-print" style="text-align: center; padding: 1rem; background: #f8fafc;">
                    <button onclick="window.print()" class="print-button">
                        🖨️ Imprimir Recibo
                    </button>
                </div>

                <!-- Body -->
                <div class="receipt-body">
                    <!-- Informações Principais -->
                    <div class="receipt-section">
                        <div class="receipt-section-title">Informações do Pagamento</div>
                        <div class="receipt-info">
                            <div class="receipt-info-item">
                                <span class="receipt-label">Recebemos de:</span>
                                <span class="receipt-value">{{ optional($receipt->client)->name }}</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Valor:</span>
                                <span class="receipt-value receipt-amount">R$ {{ number_format($receipt->amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Referente a:</span>
                                <span class="receipt-value">{{ $receipt->description }}</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Data de Emissão:</span>
                                <span class="receipt-value">{{ optional($receipt->issue_date)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($receipt->status === 'canceled')
                    <!-- Log de Cancelamento -->
                    <div class="receipt-section" style="border-left-color: #dc2626; background: #fef2f2;">
                        <div class="receipt-section-title" style="color: #dc2626;">
                            <span style="content: '⚠️'; margin-right: 0.5rem;">⚠️</span>
                            Log de Cancelamento
                        </div>
                        <div class="receipt-info">
                            <div class="receipt-info-item">
                                <span class="receipt-label">Status:</span>
                                <span class="receipt-value" style="color: #dc2626; font-weight: 700;">CANCELADO</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Cancelado em:</span>
                                <span class="receipt-value">{{ optional($receipt->canceled_at)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Cancelado por:</span>
                                <span class="receipt-value">{{ $receipt->canceled_by ?? 'Usuário não informado' }}</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-label">Motivo:</span>
                                <span class="receipt-value" style="color: #dc2626;">{{ $receipt->cancel_reason ?? 'Motivo não informado' }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($receipt->notes)
                    <!-- Observações -->
                    <div class="receipt-section">
                        <div class="receipt-section-title">Observações</div>
                        <div class="receipt-info">
                            <div class="receipt-info-item">
                                <span class="receipt-value">{{ $receipt->notes }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Footer -->
                    <div class="receipt-footer">
                        <div class="receipt-signature">
                            <div class="receipt-signature-line"></div>
                            <div class="receipt-signature-text">Assinatura / Carimbo</div>
                        </div>
                        <div class="receipt-company">
                            <div class="receipt-company-name">{{ optional($receipt->tenant)->name }}</div>
                            <div>{{ $receipt->tenant->address ?? '' }}</div>
                            <div style="margin-top: 1rem; font-size: 0.8rem; color: #9ca3af;">
                                @if($receipt->status === 'canceled')
                                    <span style="color: #dc2626; font-weight: 600;">⚠️ RECIBO CANCELADO</span><br>
                                    <span style="color: #dc2626;">Este recibo foi cancelado e não possui mais validade legal.</span>
                                @else
                                    Válido sem valor fiscal
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


