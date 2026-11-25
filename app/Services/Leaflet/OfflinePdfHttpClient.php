<?php

namespace App\Services\Leaflet;

use InvalidArgumentException;
use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Psr\Http\Message\RequestInterface;

final class OfflinePdfHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): Response
    {
        // Deny every remote resource regardless of cURL / allow_url_fopen settings.
        throw new InvalidArgumentException('PDF network resources are disabled.');
    }
}
