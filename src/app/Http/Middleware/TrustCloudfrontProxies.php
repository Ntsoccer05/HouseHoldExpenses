<?php
namespace App\Http\Middleware;
 
use Closure;
 
class TrustCloudfrontProxies
{
    public function handle($request, Closure $next)
    {
        if ($request->header('cloudfront-forwarded-proto')) {
            $headers = $request->headers;
            $headers->add(['x-forwarded-proto' => $headers->get('cloudfront-forwarded-proto')]);
            $headers->add(['x-forwarded-port' => ($headers->get('cloudfront-forwarded-proto') == 'https')?443:8080]);
        }
        return $next($request);
    }
}