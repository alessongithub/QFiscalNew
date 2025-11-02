<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <!-- Toast Container -->
        <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

        @if(session('status') === 'profile-updated')
            <script>
                (function(){
                    const c = document.getElementById('toast-container');
                    if(!c) return;
                    const el = document.createElement('div');
                    el.className = 'flex items-start max-w-sm p-3 bg-green-600 text-white rounded shadow-lg';
                    el.innerHTML = '<div class="mr-2">✅</div><div><div class="font-semibold">Perfil atualizado</div><div class="text-sm opacity-90">Suas informações foram salvas com sucesso.</div></div>';
                    c.appendChild(el);
                    setTimeout(()=>{ el.remove(); }, 4000);
                })();
            </script>
        @endif

        @if($errors->any())
            <script>
                (function(){
                    const c = document.getElementById('toast-container');
                    if(!c) return;
                    const el = document.createElement('div');
                    el.className = 'flex items-start max-w-md p-3 bg-red-600 text-white rounded shadow-lg';
                    const first = @json($errors->first());
                    el.innerHTML = '<div class="mr-2">⚠️</div><div><div class="font-semibold">Não foi possível salvar</div><div class="text-sm opacity-90">'+ first +'</div></div>';
                    c.appendChild(el);
                    setTimeout(()=>{ el.remove(); }, 6000);
                })();
            </script>
        @endif

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if(auth()->user()->is_admin)
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
