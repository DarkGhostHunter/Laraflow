<?php

namespace DarkGhostHunter\Laraflow\Http\Middleware;

use Closure;

class VerifyWebhookMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('post') // Should be a strictly POST method
            && $request->request->count() === 1 // Should only receive the "token" as input
            && $request->query('secret') === config('flow.webhook-secret') // "secret" is equal
            && is_string($token = $request->request->get('token')) // "token" is string
            && strlen($token) === 40) // "token" is 40 characters long
        {
            return $next($request);
        }

        app()->abort(404);// @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd
}