@props(['name', 'show' => false, 'maxWidth' => '2xl'])
@php
    $width = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth];
@endphp
<div x-data="accessibleModal(@js($show))"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     x-on:close.stop="show = false"
     x-on:keydown.escape.stop.prevent="show = false"
     x-on:keydown.tab.prevent="trap($event)"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
    <div aria-hidden="true" class="fixed inset-0 bg-gray-500 opacity-75" @click="show = false"></div>
    <div x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title" tabindex="-1"
         class="relative mx-auto mb-6 w-full overflow-hidden rounded-lg bg-white shadow-xl {{ $width }}">
        {{ $slot }}
    </div>
</div>
