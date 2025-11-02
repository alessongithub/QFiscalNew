<x-partner-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-xl text-gray-800">Convidar Cliente para Cadastro</h2>
		</div>
	</x-slot>

	@if(session('success'))
		<div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{!! session('success') !!}</div>
	@endif
	@if(session('error'))
		<div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ session('error') }}</div>
	@endif
	@if(session('invite_link'))
		<div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
			<div class="text-sm font-semibold text-blue-800 mb-3">Link de Cadastro Gerado:</div>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<input type="text" id="invite-link" value="{{ session('invite_link') }}" readonly class="flex-1 border rounded px-3 py-2 text-sm bg-white">
					<button onclick="copyLink('invite-link')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
						Copiar Link
					</button>
				</div>
				@if(session('whatsapp_url'))
				<div class="pt-2 border-t border-blue-200">
					<a href="{{ session('whatsapp_url') }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
						<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
							<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
						</svg>
						Abrir no WhatsApp
					</a>
				</div>
				@endif
			</div>
		</div>
		<script>
		function copyLink(id) {
			const input = document.getElementById(id);
			input.select();
			document.execCommand('copy');
			alert('Link copiado para a área de transferência!');
		}
		</script>
	@endif

	<!-- Gerar Link de Cadastro -->
	<div class="bg-white p-6 rounded shadow">
		<h3 class="text-lg font-semibold text-gray-800 mb-4">Convidar Cliente para Cadastro</h3>
		<form method="POST" action="{{ route('partner.generate-invite-link') }}" class="space-y-4">
			@csrf
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label class="block text-xs text-gray-600 mb-1">Nome do Cliente</label>
					<input type="text" name="client_name" class="w-full border rounded p-2" placeholder="Nome da empresa" required>
				</div>
				<div>
					<label class="block text-xs text-gray-600 mb-1">E-mail do Cliente</label>
					<input type="email" name="client_email" class="w-full border rounded p-2" placeholder="email@exemplo.com" required>
				</div>
			</div>
			<div>
				<label class="block text-xs text-gray-600 mb-1">Mensagem (opcional)</label>
				<textarea name="message" rows="3" class="w-full border rounded p-2" placeholder="Mensagem personalizada para o cliente..."></textarea>
			</div>
			<div class="flex gap-2">
				<button type="submit" name="action" value="email" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
					<svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
					</svg>
					Enviar por E-mail
				</button>
				<button type="submit" name="action" value="whatsapp" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
					<svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 24 24">
						<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
					</svg>
					Gerar Link para WhatsApp
				</button>
			</div>
		</form>
	</div>
</x-partner-layout>

