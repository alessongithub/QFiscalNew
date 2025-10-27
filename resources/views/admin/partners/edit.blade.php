<x-admin-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800">Editar Parceiro</h2>
	</x-slot>

	@if($errors->any())
		<div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
	@endif

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
				<button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
				<a href="{{ route('admin.partners.index') }}" class="px-4 py-2 border rounded">Voltar</a>
			</div>
		</form>
	</div>
</x-admin-layout>


