<?php

namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Laravel Herd (nginx on 127.0.0.1) terminates TLS and forwards to PHP; trust loopback
     * so Request::secure() and generated URLs match https://learning.test.
     */
    protected $proxies = ['127.0.0.1', '::1'];

    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
