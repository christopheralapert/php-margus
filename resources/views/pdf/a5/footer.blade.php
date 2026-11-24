<div class="footer">
    <p>{{ $settings->localized('leaflet.disclaimer', $doc->locale) }}</p>
    <table width="100%" autosize="1" class="footer-details">
        <tr>
            <td>{{ $settings->localized('clinic.name', $doc->locale) }} · {{ $settings->get('clinic.phone') }}</td>
            <td align="right">{PAGENO} / {nbpg}</td>
        </tr>
    </table>
</div>
