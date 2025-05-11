<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumMiddleware;

class LogSanctumRequest
{
public function handle(Request $request, Closure $next)
{
    Log::debug('Request Debug', [
        'url' => $request->fullUrl(),
        'method' => $request->method(),
        'isSecure' => $request->secure(),
        'session' => session()->all(),
        'headers' => $request->headers->all(),
        'cookies' => $request->cookies->all(),
    ]);

return (new SanctumMiddleware)->handle($request, $next);
}
}