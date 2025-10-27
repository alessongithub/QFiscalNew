<x-app-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<div>
				<h2 class="font-semibold text-xl text-gray-800 leading-tight">Enviar Pedido por E-mail</h2>
				<p class="text-sm text-gray-600 mt-1">Envie o pedido #{{ $order->number }} para o cliente</p>
			</div>
			<div class="flex items-center space-x-2">
				<div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
					{{ $order->title }}
				</div>
			</div>
		</div>
	</x-slot>

	<div class="py-6">
		<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
			@if($errors->any())
				<div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
					<svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
					</svg>
					{{ $errors->first('email') ?? $errors->first() }}
				</div>
			@endif
			
			@if(session('success'))
				<div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
					<svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
					</svg>
					{{ session('success') }}
				</div>
			@endif

			<div class="bg-white overflow-hidden shadow-lg rounded-xl">
				<div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
					<div class="flex items-center justify-between">
						<div>
							<h3 class="text-lg font-semibold text-gray-900">Configuração do E-mail</h3>
							<p class="text-sm text-gray-600 mt-1">Personalize a mensagem antes de enviar</p>
						</div>
						<div class="flex items-center space-x-2">
							<div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
								Pedido #{{ $order->number }}
							</div>
						</div>
					</div>
				</div>

				<form action="{{ route('orders.email_send', $order) }}" method="POST" class="p-6 space-y-6">
					@csrf
					
					<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
						<div class="space-y-2">
							<label class="block text-sm font-medium text-gray-700">
								<span class="flex items-center">
									<svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
									</svg>
									E-mail do Destinatário
								</span>
							</label>
							<input type="email" name="to" value="{{ old('to', $to) }}" 
								   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" 
								   placeholder="cliente@exemplo.com" required>
						</div>

						<div class="space-y-2">
							<label class="block text-sm font-medium text-gray-700">
								<span class="flex items-center">
									<svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
									</svg>
									Assunto do E-mail
								</span>
							</label>
							<input type="text" name="subject" value="{{ old('subject', $subject) }}" 
								   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200" 
								   placeholder="Pedido - Sua Empresa" required>
						</div>
					</div>

					<div class="space-y-2">
						<label class="block text-sm font-medium text-gray-700">
							<span class="flex items-center">
								<svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
								</svg>
								Template de E-mail
							</span>
						</label>
						<select name="template" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200">
							<option value="order_confirmation" @selected(old('template',$defaultTemplate)==='order_confirmation')>📋 Confirmação do Pedido</option>
							<option value="order_fulfilled" @selected(old('template',$defaultTemplate)==='order_fulfilled')>✅ Pedido Finalizado</option>
							<option value="order_shipped" @selected(old('template',$defaultTemplate)==='order_shipped')>🚚 Pedido Enviado</option>
							<option value="">✏️ Mensagem Personalizada</option>
						</select>
					</div>

					<div class="space-y-2">
						<label class="block text-sm font-medium text-gray-700">
							<span class="flex items-center">
								<svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
								</svg>
								Mensagem Personalizada
								<span class="text-xs text-gray-500 ml-2">(HTML permitido)</span>
							</span>
						</label>
						<textarea name="message" rows="8" 
								  class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 resize-none" 
								  placeholder="Digite sua mensagem personalizada aqui...&#10;&#10;Se deixar em branco, será usado o template padrão do sistema.">{{ old('message') }}</textarea>
						<p class="text-xs text-gray-500 mt-1">
							💡 <strong>Dica:</strong> Você pode usar HTML básico para formatação (negrito, itálico, links, etc.)
						</p>
					</div>

					<div class="flex items-center justify-between pt-6 border-t border-gray-200">
						<div class="flex items-center space-x-3">
							<div class="bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-sm">
								<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
								</svg>
								Detalhes do pedido serão incluídos no e-mail
							</div>
						</div>
						
						<div class="flex items-center space-x-3">
							<a href="{{ route('orders.index') }}" 
							   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition duration-200 font-medium">
								<svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
								</svg>
								Voltar
							</a>
							<button type="submit" 
									class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200 font-medium shadow-sm">
								<svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
								</svg>
								Enviar E-mail
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>


