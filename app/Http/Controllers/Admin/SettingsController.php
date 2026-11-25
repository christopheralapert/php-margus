<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Admin\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly ClinicSettings $settings) {}

    public function edit(): View
    {
        $values = $this->settings->values();

        return view('admin.settings.edit', ['values' => $values, 'warnings' => $this->settings->warnings($values['brand']['colors'])]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->save($request->validated(), $request->file('logo'));

        return to_route('admin.settings.edit')->with('success', __('settings.saved'));
    }
}
