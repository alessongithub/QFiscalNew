<x-admin-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800">Novo Parceiro</h2>
	</x-slot>

	@if($errors->any())
		<div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
	@endif

	<div class="bg-white p-4 rounded shadow max-w-2xl">
		<form action="{{ route('admin.partners.store') }}" method="POST" class="space-y-4">
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
					<label class="block text-xs text-gray-600">Logo (path público)</label>
					<input type="text" name="logo_path" value="{{ old('logo_path') }}" class="w-full border rounded p-2">
				</div>
			</div>
			<div class="flex gap-2">
				<button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Salvar</button>
				<a href="{{ route('admin.partners.index') }}" class="px-4 py-2 border rounded">Cancelar</a>
			</div>
		</form>
	</div>
</x-admin-layout>


