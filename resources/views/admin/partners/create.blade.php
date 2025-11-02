<x-admin-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800">Novo Parceiro</h2>
	</x-slot>

	@if($errors->any())
		<div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
	@endif

	<div class="bg-white p-4 rounded shadow max-w-2xl">
		<form action="{{ route('admin.partners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
			@csrf
			<div>
				<label class="block text-xs text-gray-600">Nome</label>
				<input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required>
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div>
					<label class="block text-xs text-gray-600">Slug (subdomínio)</label>
					<input type="text" name="slug" value="{{ old('slug') }}" class="w-full border rounded p-2" required>
				</div>
				<div>
					<label class="block text-xs text-gray-600">Domínio (opcional)</label>
					<input type="text" name="domain" value="{{ old('domain') }}" class="w-full border rounded p-2">
				</div>
			</div>
			<div class="grid grid-cols-3 gap-4">
				<div>
					<label class="block text-xs text-gray-600">Comissão (0 a 1)</label>
					<input type="number" step="0.0001" min="0" max="1" name="commission_percent" value="{{ old('commission_percent', '0.3000') }}" class="w-full border rounded p-2">
				</div>
				<div>
					<label class="block text-xs text-gray-600">Tema</label>
					<select name="theme" class="w-full border rounded p-2">
						<option value="light" @selected(old('theme','light')==='light')>Claro</option>
						<option value="dark" @selected(old('theme')==='dark')>Escuro</option>
					</select>
				</div>
				<div class="flex items-center gap-2">
					<label class="block text-xs text-gray-600">Ativo</label>
					<input type="checkbox" name="active" value="1" {{ old('active') ? 'checked' : '' }}>
				</div>
			</div>
			<div class="grid grid-cols-3 gap-4">
				<div>
					<label class="block text-xs text-gray-600">Cor primária (#hex)</label>
					<input type="text" name="primary_color" value="{{ old('primary_color','#2563eb') }}" class="w-full border rounded p-2">
				</div>
				<div>
					<label class="block text-xs text-gray-600">Cor secundária (#hex)</label>
					<input type="text" name="secondary_color" value="{{ old('secondary_color','#0ea5e9') }}" class="w-full border rounded p-2">
				</div>
				<div>
					<label class="block text-xs text-gray-600 mb-2">Logo (path público)</label>
					<div class="space-y-2">
						<div class="relative">
							<input type="text" name="logo_path" id="logo_path" value="{{ old('logo_path') }}" onclick="document.getElementById('logo_file').click()" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition cursor-pointer bg-white" placeholder="Clique para selecionar o logo">
							<input type="file" name="logo" id="logo_file" accept="image/*" class="hidden" onchange="handleLogoSelect(event)">
						</div>
						<div id="logo_preview" class="hidden mt-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
							<div class="flex items-center gap-3">
								<img id="logo_preview_img" src="" alt="Preview" class="h-12 w-auto object-contain rounded">
								<div class="flex-1">
									<p class="text-xs text-gray-600 font-medium" id="logo_preview_name"></p>
									<p class="text-xs text-gray-400" id="logo_preview_size"></p>
								</div>
								<button type="button" onclick="clearLogo()" class="text-red-500 hover:text-red-700 text-sm">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
									</svg>
								</button>
							</div>
						</div>
					</div>
				</div>
				<script>
				function handleLogoSelect(event) {
					const file = event.target.files[0];
					const logoPath = document.getElementById('logo_path');
					const preview = document.getElementById('logo_preview');
					const previewImg = document.getElementById('logo_preview_img');
					const previewName = document.getElementById('logo_preview_name');
					const previewSize = document.getElementById('logo_preview_size');
					
					if (file) {
						logoPath.value = file.name;
						previewName.textContent = file.name;
						previewSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
						
						const reader = new FileReader();
						reader.onload = function(e) {
							previewImg.src = e.target.result;
							preview.classList.remove('hidden');
						};
						reader.readAsDataURL(file);
					}
				}
				
				function clearLogo() {
					document.getElementById('logo_file').value = '';
					document.getElementById('logo_path').value = '';
					document.getElementById('logo_preview').classList.add('hidden');
				}
				</script>
			</div>
			<div class="flex gap-2">
				<button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Salvar</button>
				<a href="{{ route('admin.partners.index') }}" class="px-4 py-2 border rounded">Cancelar</a>
			</div>
		</form>
	</div>
</x-admin-layout>


