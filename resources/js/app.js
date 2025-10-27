import './bootstrap';
// Evitar múltiplas instâncias Alpine em telas que já carregam Alpine via CDN (ex.: POS)
if (window.Alpine && window.Livewire) {
    try {
        // Livewire alerta sobre Alpine duplicado; forçamos usar o existente
        window.deferLoadingAlpine = function(callback){ callback(); };
    } catch (_) {}
}

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
