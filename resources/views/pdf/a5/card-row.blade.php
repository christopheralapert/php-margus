<table width="100%" autosize="1" class="card-row card-columns-{{ $columns }}{{ $row === $rowCount - 1 ? ' card-last' : '' }}"><tr>
    @for ($column = 0; $column < $columns; $column++)
        @php($item = $items->get($row + $column * $rowCount))
        <td width="6mm" class="item-icon">@if ($item){!! $icons->image($icon, $colors['base'], 4) !!}@endif</td>
        <td width="{{ $columns === 2 ? '50%' : '100%' }}" class="item-copy">@if ($item){{ $item->getTranslation('text', $doc->locale) }}@endif</td>
    @endfor
</tr></table>
