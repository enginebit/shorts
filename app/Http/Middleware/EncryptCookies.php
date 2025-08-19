<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

final class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * Laravel expects the XSRF-TOKEN cookie to be readable by the browser
     * so the client can send it back via the X-XSRF-TOKEN header.
     */
    protected $except = [
        'XSRF-TOKEN',
    ];
}

