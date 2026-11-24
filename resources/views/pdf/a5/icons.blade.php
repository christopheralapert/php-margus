@inject('icons', 'App\Services\Leaflet\PdfIcons')
<html lang="{{ app()->getLocale() }}"><body>
<h2>{{ __('pdf.icons_title') }}</h2>
<p>{{ __('pdf.icons_comparison') }}</p>
<table width="100%" autosize="1" class="icon-sheet">
    <tr><td></td><td>{{ __('pdf.icons_ink') }}</td><td>{{ __('pdf.icons_theme') }}</td><td>{{ __('pdf.icons_inline') }}</td></tr>
    @foreach ($icons::NAMES as $name)
        <tr>
            <td>{{ __('pdf.icons.'.$name) }}</td>
            <td>{!! $icons->image($name, $settings->get('brand.colors')['ink']) !!}</td>
            <td>{!! $icons->image($name, '#7C5CD6') !!}</td>
            <td>{!! str_replace('<svg ', '<svg width="6mm" height="6mm" ', $icons->svg($name, '#7C5CD6')) !!}</td>
        </tr>
    @endforeach
</table>
</body></html>
