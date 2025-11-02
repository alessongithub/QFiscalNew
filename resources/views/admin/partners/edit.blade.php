<x-admin-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800">Editar Parceiro</h2>
	</x-slot>

	<!-- Toast Container -->
	<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 400px;"></div>

    <div class="bg-white p-6 rounded shadow max-w-3xl">
        <form action="{{ route('admin.partners.update', $partner) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
			@csrf @method('PUT')
			<div>
				<label class="block text-xs text-gray-600">Nome</label>
				<input type="text" name="name" value="{{ old('name', $partner->name) }}" class="w-full border rounded p-2" required>
			</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="text-sm font-semibold text-gray-800">Dados gerais</div>
                    <div>
                        <label class="block text-xs text-gray-600">Slug (subdomínio)</label>
                        <input type="text" name="slug" value="{{ old('slug', $partner->slug) }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Domínio (opcional)</label>
                        <input type="text" name="domain" value="{{ old('domain', $partner->domain) }}" class="w-full border rounded p-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600">CNPJ</label>
                            <input id="cnpj" type="text" name="cnpj" value="{{ old('cnpj', $partner->cnpj) }}" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600">CRC</label>
                            <input type="text" name="crc" value="{{ old('crc', $partner->crc) }}" class="w-full border rounded p-2">
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="text-sm font-semibold text-gray-800">Branding</div>
                    <div>
                        <label class="block text-xs text-gray-600">Logo</label>
                        @if($partner->logo_path)
                            <div class="mb-2"><img src="{{ Storage::disk('public')->url($partner->logo_path) }}" class="h-12" /></div>
                        @endif
                        <input type="file" name="logo" accept="image/*" class="w-full border rounded p-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600">Cor primária</label>
                            <input id="primary_color" type="color" name="primary_color" value="{{ old('primary_color', $partner->primary_color ?? '#2563eb') }}" class="w-16 h-10 p-1 border rounded">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600">Cor secundária</label>
                            <input id="secondary_color" type="color" name="secondary_color" value="{{ old('secondary_color', $partner->secondary_color ?? '#0ea5e9') }}" class="w-16 h-10 p-1 border rounded">
                        </div>
                    </div>
                </div>
            </div>
			<div class="grid grid-cols-3 gap-4">
				<div>
					<label class="block text-xs text-gray-600">Comissão (0 a 1)</label>
					<input type="number" step="0.0001" min="0" max="1" name="commission_percent" value="{{ old('commission_percent', $partner->commission_percent) }}" class="w-full border rounded p-2">
				</div>
				<div>
					<label class="block text-xs text-gray-600">Tema</label>
					<select name="theme" class="w-full border rounded p-2">
						<option value="light" @selected(old('theme',$partner->theme)==='light')>Claro</option>
						<option value="dark" @selected(old('theme',$partner->theme)==='dark')>Escuro</option>
					</select>
				</div>
				<div class="flex items-center gap-2">
					<label class="block text-xs text-gray-600">Ativo</label>
					<input type="checkbox" name="active" value="1" {{ old('active', $partner->active) ? 'checked' : '' }}>
				</div>
			</div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-gray-600">Contato</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $partner->contact_name) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">E-mail</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $partner->contact_email) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">WhatsApp</label>
                    <input id="contact_phone" type="text" name="contact_phone" value="{{ old('contact_phone', $partner->contact_phone) }}" class="w-full border rounded p-2">
                </div>
            </div>
			<div class="grid grid-cols-3 gap-4">
				<div>
					<label class="block text-xs text-gray-600">Cor primária (#hex)</label>
					<input id="primary_color_hex" type="text" value="{{ old('primary_color',$partner->primary_color) }}" class="w-full border rounded p-2" readonly>
    </div>

    <script>
    function maskCNPJ(v){
        v = v.replace(/\D/g, '');
        v = v.replace(/(\d{2})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        return v;
    }
    function maskPhone(v){
        v = v.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0,11);
        if (v.length > 10) return v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        if (v.length > 6) return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        if (v.length > 2) return v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        return v;
    }
    const cnpjInput = document.getElementById('cnpj');
    const phoneInput = document.getElementById('contact_phone');
    if (cnpjInput){ cnpjInput.addEventListener('input', (e)=>{ e.target.value = maskCNPJ(e.target.value); }); }
    if (phoneInput){ phoneInput.addEventListener('input', (e)=>{ e.target.value = maskPhone(e.target.value); }); }
    // sincroniza os inputs de texto com o color picker e garante prefixo '#'
    const pc = document.getElementById('primary_color');
    const sc = document.getElementById('secondary_color');
    const pcHex = document.getElementById('primary_color_hex');
    const scHex = document.getElementById('secondary_color_hex');
    function normalizeHex(v){ if(!v) return ''; v = v.trim(); if(!v.startsWith('#')) v = '#' + v; return v.length===4||v.length===7 ? v : v; }
    if (pc && pcHex){ pc.addEventListener('input', ()=>{ pcHex.value = normalizeHex(pc.value); }); pcHex.addEventListener('input', ()=>{ pc.value = normalizeHex(pcHex.value); }); }
    if (sc && scHex){ sc.addEventListener('input', ()=>{ scHex.value = normalizeHex(sc.value); }); scHex.addEventListener('input', ()=>{ sc.value = normalizeHex(scHex.value); }); }
    </script>
				<div>
					<label class="block text-xs text-gray-600">Cor secundária (#hex)</label>
					<input id="secondary_color_hex" type="text" value="{{ old('secondary_color',$partner->secondary_color) }}" class="w-full border rounded p-2" readonly>
				</div>
				<div>
					<label class="block text-xs text-gray-600">Logo (path público)</label>
					<input type="text" name="logo_path" value="{{ old('logo_path',$partner->logo_path) }}" class="w-full border rounded p-2">
				</div>
			</div>
			<div class="flex gap-2">
				<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
				<a href="{{ route('admin.partners.index') }}" class="px-4 py-2 border rounded">Voltar</a>
			</div>
		</form>
	</div>

	<script>
	// Sistema de Toast Dinâmico
	const toastContainer = document.getElementById('toast-container');

	function showToast(message, type = 'error') {
		const toast = document.createElement('div');
		const id = 'toast-' + Date.now();
		toast.id = id;
		
		const colors = {
			error: {
				bg: 'bg-red-50',
				border: 'border-red-200',
				text: 'text-red-800',
				icon: 'text-red-500',
				iconBg: 'bg-red-100'
			},
			success: {
				bg: 'bg-green-50',
				border: 'border-green-200',
				text: 'text-green-800',
				icon: 'text-green-500',
				iconBg: 'bg-green-100'
			},
			warning: {
				bg: 'bg-yellow-50',
				border: 'border-yellow-200',
				text: 'text-yellow-800',
				icon: 'text-yellow-500',
				iconBg: 'bg-yellow-100'
			},
			info: {
				bg: 'bg-blue-50',
				border: 'border-blue-200',
				text: 'text-blue-800',
				icon: 'text-blue-500',
				iconBg: 'bg-blue-100'
			}
		};

		const color = colors[type] || colors.error;
		
		const iconSvg = type === 'error' 
			? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
			: type === 'success'
			? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
			: type === 'warning'
			? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
			: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';

		toast.className = `${color.bg} ${color.border} border-l-4 shadow-lg rounded-lg p-4 mb-2 transform transition-all duration-300 ease-in-out`;
		toast.style.opacity = '0';
		toast.style.transform = 'translateX(100%)';
		
		toast.innerHTML = `
			<div class="flex items-start">
				<div class="flex-shrink-0">
					<div class="${color.iconBg} ${color.icon} rounded-full p-2">
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							${iconSvg}
						</svg>
					</div>
				</div>
				<div class="ml-3 flex-1">
					<p class="${color.text} text-sm font-medium">${message}</p>
				</div>
				<button onclick="closeToast('${id}')" class="ml-4 flex-shrink-0 ${color.text} hover:opacity-75 focus:outline-none">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
		`;

		toastContainer.appendChild(toast);

		// Animações
		setTimeout(() => {
			toast.style.opacity = '1';
			toast.style.transform = 'translateX(0)';
		}, 10);

		// Auto remover após 5 segundos
		setTimeout(() => {
			closeToast(id);
		}, 5000);
	}

	function closeToast(id) {
		const toast = document.getElementById(id);
		if (toast) {
			toast.style.opacity = '0';
			toast.style.transform = 'translateX(100%)';
			setTimeout(() => {
				toast.remove();
			}, 300);
		}
	}

	// Traduzir mensagens de erro de validação para português amigável
	function getFriendlyMessage(field, error) {
		const messages = {
			'required': `O campo ${getFieldName(field)} é obrigatório.`,
			'email': `O e-mail informado não é válido.`,
			'max': {
				'name': 'O nome deve ter no máximo 150 caracteres.',
				'slug': 'O slug deve ter no máximo 100 caracteres.',
				'domain': 'O domínio deve ter no máximo 190 caracteres.',
				'cnpj': 'O CNPJ deve ter no máximo 20 caracteres.',
				'crc': 'O CRC deve ter no máximo 50 caracteres.',
				'contact_name': 'O nome do contato deve ter no máximo 150 caracteres.',
				'contact_email': 'O e-mail deve ter no máximo 190 caracteres.',
				'contact_phone': 'O telefone deve ter no máximo 50 caracteres.',
				'primary_color': 'A cor deve ter no máximo 7 caracteres.',
				'secondary_color': 'A cor deve ter no máximo 7 caracteres.',
				'logo': 'O arquivo de logo deve ter no máximo 2MB.'
			},
			'unique': {
				'slug': 'Este slug já está sendo usado por outro parceiro.',
				'domain': 'Este domínio já está sendo usado por outro parceiro.'
			},
			'alpha_dash': 'O slug só pode conter letras, números, traços e underscores.',
			'numeric': 'O valor da comissão deve ser um número.',
			'between': 'A comissão deve estar entre 0 e 1.',
			'in': 'O tema selecionado é inválido.',
			'boolean': 'O valor do campo ativo é inválido.',
			'image': 'O arquivo deve ser uma imagem válida (JPG, PNG, GIF, etc.).'
		};

		if (error.includes('required')) {
			return messages.required;
		}

		if (error.includes('email')) {
			return messages.email;
		}

		if (error.includes('max')) {
			return messages.max[field] || `O campo ${getFieldName(field)} excede o tamanho máximo permitido.`;
		}

		if (error.includes('unique')) {
			return messages.unique[field] || `Este ${getFieldName(field)} já está em uso.`;
		}

		if (error.includes('alpha_dash')) {
			return messages.alpha_dash;
		}

		if (error.includes('numeric')) {
			return messages.numeric;
		}

		if (error.includes('between')) {
			return messages.between;
		}

		if (error.includes('in')) {
			return messages.in;
		}

		if (error.includes('boolean')) {
			return messages.boolean;
		}

		if (error.includes('image')) {
			return messages.image;
		}

		return error;
	}

	function getFieldName(field) {
		if (!field) return 'Campo';
		const names = {
			'name': 'Nome',
			'slug': 'Slug',
			'domain': 'Domínio',
			'cnpj': 'CNPJ',
			'crc': 'CRC',
			'contact_name': 'Nome do Contato',
			'contact_email': 'E-mail',
			'contact_phone': 'Telefone',
			'commission_percent': 'Comissão',
			'theme': 'Tema',
			'primary_color': 'Cor Primária',
			'secondary_color': 'Cor Secundária',
			'logo': 'Logo',
			'active': 'Status Ativo'
		};
		return names[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
	}

	// Capturar erros do Laravel
	@if($errors->any())
		@foreach($errors->getMessages() as $field => $messages)
			@foreach($messages as $message)
				showToast(getFriendlyMessage('{{ $field }}', '{{ addslashes($message) }}'), 'error');
			@endforeach
		@endforeach
	@endif

	// Capturar mensagens de sucesso
	@if(session('success'))
		showToast('{{ addslashes(session('success')) }}', 'success');
	@endif

	// Capturar erros gerais
	@if(session('error'))
		showToast('{{ addslashes(session('error')) }}', 'error');
	@endif

	// Tratamento de erros de submissão do formulário
	document.querySelector('form').addEventListener('submit', function(e) {
		const form = this;
		const submitBtn = form.querySelector('button[type="submit"]');
		const originalText = submitBtn.textContent;
		
		// Validação básica antes de enviar
		const requiredFields = form.querySelectorAll('[required]');
		let hasErrors = false;
		
		requiredFields.forEach(field => {
			if (!field.value.trim()) {
				const fieldName = getFieldName(field.name);
				showToast(`O campo ${fieldName} é obrigatório.`, 'error');
				hasErrors = true;
			}
		});

		if (hasErrors) {
			e.preventDefault();
			return false;
		}
		
		submitBtn.disabled = true;
		submitBtn.textContent = 'Salvando...';

		// Reabilitar botão caso ocorra erro após 5 segundos
		setTimeout(() => {
			if (submitBtn.disabled) {
				submitBtn.disabled = false;
				submitBtn.textContent = originalText;
				showToast('O envio está demorando mais que o esperado. Verifique sua conexão.', 'warning');
			}
		}, 5000);
	});

	// Tratamento de erros de upload de arquivo
	const logoInput = document.querySelector('input[name="logo"]');
	if (logoInput) {
		logoInput.addEventListener('change', function(e) {
			const file = e.target.files[0];
			if (file) {
				// Validar tamanho máximo (2MB)
				if (file.size > 2048 * 1024) {
					showToast('O arquivo de logo deve ter no máximo 2MB.', 'error');
					e.target.value = '';
					return;
				}
				// Validar tipo de arquivo
				if (!file.type.match('image.*')) {
					showToast('O arquivo deve ser uma imagem válida (JPG, PNG, GIF, etc.).', 'error');
					e.target.value = '';
					return;
				}
			}
		});
	}
	</script>
</x-admin-layout>


