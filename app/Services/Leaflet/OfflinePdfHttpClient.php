<?php

namespace App\Services\Leaflet;

use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Psr\Http\Message\RequestInterface;

final class OfflinePdfHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): Response
    {
        // Deny every remote resource regardless of cURL / allow_url_fopen settings.
        return new Response(403);
    }
}
