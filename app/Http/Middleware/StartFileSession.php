<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as BaseStartSession;

class StartFileSession extends BaseStartSession
{
    protected function addCookieToResponse($response, $session)
    {
        return $response;
    }
}
