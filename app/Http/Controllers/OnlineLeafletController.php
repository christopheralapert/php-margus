<?php

namespace App\Http\Controllers;

use App\Services\Leaflet\OnlineLeafletProcedures;
use Illuminate\Contracts\View\View;

final class OnlineLeafletController extends Controller
{
    public function __invoke(string $combo, OnlineLeafletProcedures $procedures): View
    {
        return view('online.show', ['procedures' => $procedures->resolve($combo)]);
    }
}
