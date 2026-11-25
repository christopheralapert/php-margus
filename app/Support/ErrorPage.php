<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;

final class ErrorPage
{
    public function locale(Request $request): string
    {
        $segment = $request->segment(1);
        if (in_array($segment, ['et', 'en'], true)) {
            return $segment;
        }

        $cookie = $request->cookie('locale');
        if (is_string($cookie) && ! in_array($cookie, ['et', 'en'], true)) {
            // Unmatched routes never pass through EncryptCookies.
            try {
                $encrypter = app('encrypter');
                $cookie = CookieValuePrefix::validate('locale', $encrypter->decrypt($cookie, false), $encrypter->getAllKeys());
            } catch (DecryptException) {
                $cookie = null;
            }
        }

        return in_array($cookie, ['et', 'en'], true) ? $cookie : 'et';
    }
}
