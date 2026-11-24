@if ($doc->firstName)<p>{{ __('wizard.recipient', ['name' => $doc->firstName], $doc->locale) }}</p>@endif
@if ($doc->date)<p>{{ __('wizard.procedure_date', ['date' => $doc->formattedDate()], $doc->locale) }}</p>@endif
