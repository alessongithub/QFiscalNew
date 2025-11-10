<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mensagem Enviada') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 w-full">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-12 text-center">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-white bg-opacity-20 mb-4">
                        <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">Mensagem Enviada com Sucesso!</h1>
                    <p class="text-green-100 text-lg">Recebemos seu chamado</p>
                </div>

                <div class="p-8 text-center">
                    <div class="mb-6">
                        <p class="text-gray-700 text-lg mb-4">
                            Sua mensagem foi enviada para nossa equipe de suporte e será respondida o mais breve possível.
                        </p>
                        <p class="text-gray-600">
                            Você receberá uma resposta por email em <strong class="text-gray-900">{{ auth()->user()->email }}</strong>
                        </p>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-6 text-left">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Dica:</strong> Verifique sua caixa de entrada e a pasta de spam. Nossa resposta pode levar de algumas horas até 1 dia útil.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Voltar ao Dashboard
                        </a>
                        <a href="{{ route('support.create') }}" class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg shadow transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Enviar Nova Mensagem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

