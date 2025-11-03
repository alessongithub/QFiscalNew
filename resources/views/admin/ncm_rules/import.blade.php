<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-upload mr-2"></i>
                Importar Regras NCM
            </h2>
            <a href="{{ route('admin.ncm_rules.index') }}" class="text-gray-600 hover:text-gray-800 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>
        <p class="mt-2 text-sm text-gray-600">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Atenção:</strong> As regras importadas serão <strong>globais</strong> e aplicadas a <strong>todos os tenants</strong>.
        </p>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Erros encontrados:</strong>
                    </div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Como importar regras NCM</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Você pode importar múltiplas regras NCM de uma vez usando um arquivo CSV. 
                    O arquivo deve seguir o formato do CSV exportado.
                </p>
            </div>

            <!-- Instruções -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-blue-900 mb-2 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    Instruções
                </h4>
                <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                    <li>Exporte as regras atuais usando o botão "Exportar CSV"</li>
                    <li>Edite o arquivo CSV preenchido (adicionar, modificar ou remover regras)</li>
                    <li>Salve o arquivo como CSV (separador: ponto e vírgula)</li>
                    <li>Faça o upload do arquivo preenchido usando o formulário abaixo</li>
                </ol>
            </div>

            <!-- Formato do CSV -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <i class="fas fa-table mr-2"></i>
                    Formato do CSV
                </h4>
                <div class="text-sm text-gray-700">
                    <p class="mb-2"><strong>Colunas obrigatórias:</strong></p>
                    <ul class="list-disc list-inside space-y-1 mb-3">
                        <li><strong>NCM:</strong> Código NCM sem pontos (apenas dígitos, 4 a 20 caracteres)</li>
                        <li><strong>Requer GTIN:</strong> 1 para obrigatório, 0 para opcional (ou true/false, sim/não)</li>
                        <li><strong>Observação:</strong> Descrição opcional (máximo 255 caracteres)</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        O separador deve ser ponto e vírgula (;) e o arquivo deve estar em UTF-8. NCMs são salvos sem pontos.
                    </p>
                </div>
            </div>

            <!-- Formulário de upload -->
            <form method="POST" action="{{ route('admin.ncm_rules.import.store') }}" enctype="multipart/form-data" class="space-y-6" id="importForm">
                @csrf
                
                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-csv mr-1"></i>
                        Arquivo CSV para importar
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Selecione um arquivo</span>
                                    <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="sr-only" required>
                                </label>
                                <p class="pl-1">ou arraste aqui</p>
                            </div>
                            <p class="text-xs text-gray-500">CSV ou TXT até 5MB</p>
                            <p class="text-xs text-gray-400 mt-1" id="fileName"></p>
                        </div>
                    </div>
                    @error('csv_file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.ncm_rules.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed"
                            id="submitBtn">
                        <i class="fas fa-upload mr-2"></i>
                        Importar Regras
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('csv_file');
            const fileName = document.getElementById('fileName');
            const form = document.getElementById('importForm');
            const submitBtn = document.getElementById('submitBtn');

            // Mostrar nome do arquivo selecionado
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileName.textContent = `Arquivo selecionado: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                    fileName.className = 'text-xs text-green-600 mt-1';
                } else {
                    fileName.textContent = '';
                }
            });

            // Drag and drop
            const dropZone = fileInput.closest('.border-dashed');
            let isDragging = false;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('border-blue-400', 'bg-blue-50');
                    isDragging = true;
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                    isDragging = false;
                }, false);
            });

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    fileInput.files = files;
                    const file = files[0];
                    fileName.textContent = `Arquivo selecionado: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                    fileName.className = 'text-xs text-green-600 mt-1';
                }
            }, false);

            // Feedback de envio
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importando...';
            });
        });
    </script>
</x-admin-layout>

