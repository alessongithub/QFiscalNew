@extends('layouts.app')

@section('title', 'Cancelar OS')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('service_orders.index') }}">Ordens de Serviço</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('service_orders.show', $serviceOrder) }}">OS #{{ $serviceOrder->number }}</a></li>
                        <li class="breadcrumb-item active">Cancelar OS</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="mdi mdi-cancel text-danger"></i>
                    Cancelar OS #{{ $serviceOrder->number }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-alert-circle"></i>
                        Confirmação de Cancelamento
                    </h5>
                </div>
                <div class="card-body">
                    @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">
                                <i class="mdi mdi-alert-circle"></i>
                                ATENÇÃO: OS JÁ FINALIZADA!
                            </h6>
                            <p class="mb-2">
                                Esta OS já foi <strong>finalizada e entregue ao cliente</strong>. O cancelamento irá:
                            </p>
                            <ul class="mb-0">
                                <li>Reversar todo o estoque utilizado</li>
                                <li>Cancelar recebíveis e estornar pagamentos</li>
                                <li>Cancelar garantias ativas</li>
                                <li><strong>Será necessário recolher o equipamento do cliente</strong></li>
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="mdi mdi-alert"></i>
                                Atenção!
                            </h6>
                            <p class="mb-0">
                                Esta ação irá <strong>cancelar permanentemente</strong> a OS #{{ $serviceOrder->number }}. 
                                Todas as reversões necessárias serão aplicadas automaticamente.
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('service_orders.cancel', $serviceOrder) }}" method="POST" id="cancelForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">
                                Motivo do Cancelamento <span class="text-danger">*</span>
                            </label>
                            <textarea 
                                class="form-control @error('cancellation_reason') is-invalid @enderror" 
                                id="cancellation_reason" 
                                name="cancellation_reason" 
                                rows="4" 
                                placeholder="Descreva detalhadamente o motivo do cancelamento..."
                                required
                                minlength="10"
                                maxlength="1000"
                            >{{ old('cancellation_reason') }}</textarea>
                            <div class="form-text">
                                Mínimo 10 caracteres, máximo 1000 caracteres.
                            </div>
                            @error('cancellation_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Observações Adicionais</label>
                            <textarea 
                                class="form-control" 
                                id="notes" 
                                name="notes" 
                                rows="3" 
                                placeholder="Observações adicionais sobre o cancelamento..."
                            >{{ old('notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input 
                                    class="form-check-input @error('confirm_cancellation') is-invalid @enderror" 
                                    type="checkbox" 
                                    id="confirm_cancellation" 
                                    name="confirm_cancellation" 
                                    value="1"
                                    required
                                >
                                <label class="form-check-label" for="confirm_cancellation">
                                    @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
                                        <strong>Confirmo que desejo cancelar esta OS finalizada e entendo que será necessário recolher o equipamento do cliente.</strong>
                                    @else
                                        <strong>Confirmo que desejo cancelar esta OS e entendo que esta ação não pode ser desfeita.</strong>
                                    @endif
                                </label>
                                @error('confirm_cancellation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" id="cancelButton" disabled>
                                <i class="mdi mdi-cancel"></i>
                                Cancelar OS
                            </button>
                            <a href="{{ route('service_orders.show', $serviceOrder) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i>
                                Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Informações da OS -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-information"></i>
                        Informações da OS
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-4"><strong>Número:</strong></div>
                        <div class="col-8">#{{ $serviceOrder->number }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Cliente:</strong></div>
                        <div class="col-8">{{ $serviceOrder->client->name }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Status:</strong></div>
                        <div class="col-8">
                            <span class="badge bg-{{ $serviceOrder->status === 'open' ? 'primary' : ($serviceOrder->status === 'in_progress' ? 'warning' : ($serviceOrder->status === 'finalized' ? 'success' : 'info')) }}">
                                {{ ucfirst(str_replace('_', ' ', $serviceOrder->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Valor Total:</strong></div>
                        <div class="col-8">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Criada em:</strong></div>
                        <div class="col-8">{{ $serviceOrder->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Análise de Impactos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-line"></i>
                        Análise de Impactos
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($impacts['stock_impact']))
                        <div class="mb-3">
                            <h6 class="text-primary">
                                <i class="mdi mdi-package-variant"></i>
                                Impacto no Estoque
                            </h6>
                            @foreach($impacts['stock_impact'] as $stock)
                                <div class="small mb-1">
                                    <strong>{{ $stock['product_name'] }}</strong><br>
                                    <span class="text-muted">
                                        Estoque atual: {{ number_format($stock['current_stock'], 0, ',', '.') }} → 
                                        {{ number_format($stock['new_stock'], 0, ',', '.') }} 
                                        (+{{ number_format($stock['quantity_to_restore'], 0, ',', '.') }})
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($impacts['financial_impact']['payment_received']))
                        <div class="mb-3">
                            <h6 class="text-warning">
                                <i class="mdi mdi-currency-usd"></i>
                                Impacto Financeiro
                            </h6>
                            <div class="small">
                                <strong>Valor a ser estornado:</strong><br>
                                R$ {{ number_format($impacts['financial_impact']['amount_to_refund'], 2, ',', '.') }}
                            </div>
                        </div>
                    @endif

                    @if(!empty($impacts['warnings']))
                        <div class="mb-3">
                            <h6 class="text-danger">
                                <i class="mdi mdi-alert"></i>
                                Avisos Importantes
                            </h6>
                            @foreach($impacts['warnings'] as $warning)
                                <div class="small text-danger mb-1">
                                    <i class="mdi mdi-alert-circle"></i>
                                    {{ $warning }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(empty($impacts['stock_impact']) && empty($impacts['financial_impact']['payment_received']))
                        <div class="text-muted text-center">
                            <i class="mdi mdi-check-circle text-success"></i><br>
                            <small>Nenhum impacto significativo identificado.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirm_cancellation');
    const cancelButton = document.getElementById('cancelButton');
    const cancelForm = document.getElementById('cancelForm');

    // Habilitar/desabilitar botão baseado no checkbox
    confirmCheckbox.addEventListener('change', function() {
        cancelButton.disabled = !this.checked;
    });

    // Confirmação adicional antes de enviar
    cancelForm.addEventListener('submit', function(e) {
        @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
            if (!confirm('ATENÇÃO: Esta OS já foi finalizada e entregue ao cliente!\n\nO cancelamento irá:\n- Reversar estoque\n- Cancelar recebíveis\n- Cancelar garantias\n- Será necessário recolher o equipamento\n\nTem certeza que deseja continuar?')) {
                e.preventDefault();
            }
        @else
            if (!confirm('Tem certeza que deseja cancelar esta OS? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        @endif
    });
});
</script>
@endsection
