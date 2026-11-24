<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function __invoke(Settings $settings): View
    {
        return view('privacy', [
            'clinicName' => $settings->localized('clinic.name'),
            'clinicAddress' => $settings->localized('clinic.address'),
            'clinicEmail' => $settings->get('clinic.email'),
            'months' => $settings->get('retention.saved_leaflet_months', 12),
            'days' => $settings->get('retention.unverified_account_days', 30),
        ]);
    }
}
