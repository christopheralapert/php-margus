@props(['slug', 'color'])
@php
    // Only read bundled icons from the category directory, never a user-supplied path.
    $path = preg_match('/\A[a-z0-9-]+\z/', $slug) ? public_path('icons/categories/'.$slug.'.svg') : null;
@endphp
@if ($path && is_file($path))
    <span aria-hidden="true" class="block h-8 w-8 shrink-0 [&_svg]:h-full [&_svg]:w-full" style="color: {{ (new \App\Services\Leaflet\ThemeColor($color))->dark() }}">
        {!! file_get_contents($path) !!}
    </span>
@endif
