<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Sangria</h2>
    </x-slot>

    <div class="bg-white p-4 rounded shadow max-w-md">
        <form method="POST" action="{{ route('cash_withdrawals.update', $withdrawal) }}" class="space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm">Data</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm">Valor</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ $withdrawal->amount }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm">Motivo</label>
                <input type="text" name="reason" maxlength="255" value="{{ $withdrawal->reason }}" class="w-full border rounded p-2" required>
            </div>
            <div class="pt-2">
                <button class="px-4 py-2 bg-red-600 text-white rounded">Salvar</button>
                <a href="{{ route('cash_withdrawals.index', ['date' => $date]) }}" class="ml-2">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast para mensagens de sucesso e erro
    const flashSuccess = @json(session('success'));
    const flashError = @json(session('error'));
    const validationErrors = @json($errors->all());
    
    if (flashSuccess) {
        showToast(flashSuccess, 'success');
    }
    if (flashError) {
        showToast(flashError, 'error');
    }
    if (validationErrors && validationErrors.length > 0) {
        validationErrors.forEach(error => {
            showToast(error, 'error');
        });
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full`;
        
        if (type === 'success') {
            toast.className += ' bg-green-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        } else {
            toast.className += ' bg-red-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        }
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 4 segundos
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }
});
</script>

