@props(['href', 'active' => false, 'icon', 'label', 'badge' => null])

<a href="{{ $href }}" 
   class="sidebar-link flex items-center text-white hover:bg-green-600 transition-colors relative group px-6 py-2 {{ $active ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
        <svg class="sidebar-icon w-5 h-5 flex-shrink-0 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $icon !!}
        </svg>
        <span class="sidebar-text flex-1">{{ $label }}</span>
        
        @if($badge)
            <span class="sidebar-badge ml-2 text-xs bg-red-600 rounded-full px-2 py-0.5">{{ $badge }}</span>
        @endif
        
        <!-- Tooltip -->
        <div class="tooltip absolute left-16 top-1/2 transform -translate-y-1/2 bg-gray-900 text-white px-2 py-1 rounded text-sm whitespace-nowrap z-50 shadow-lg">
            {{ $label }}
            @if($badge)
                <span class="ml-1 text-xs bg-red-600 rounded-full px-1">{{ $badge }}</span>
            @endif
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-1 border-r-4 border-r-gray-900 border-y-4 border-y-transparent"></div>
        </div>
</a>
