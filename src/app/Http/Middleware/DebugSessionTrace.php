<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 【一時診断用】並行リクエストで401になる問題の調査用。
 * セッションCookieの生値・解決されたセッションID・認証状態をリクエストごとに記録する。
 * 原因特定後に削除すること。
 */
class DebugSessionTrace
{
    public function handle(Request $request, Closure $next)
    {
        $cookieName = config('session.cookie');
        $rawCookie = $request->cookie($cookieName);
        $allCookieNames = array_keys($request->cookies->all());
        $rawHeaderCookie = $request->headers->get('Cookie');

        $response = $next($request);

        Log::info('session_trace', [
            'micro' => microtime(true),
            'path' => $request->path(),
            'method' => $request->method(),
            'configured_cookie_name' => $cookieName,
            'all_cookie_names' => $allCookieNames,
            'raw_cookie_header_present' => $rawHeaderCookie !== null,
            'raw_cookie_header_len' => $rawHeaderCookie ? strlen($rawHeaderCookie) : 0,
            'raw_cookie_present' => $rawCookie !== null,
            'raw_cookie_len' => $rawCookie ? strlen($rawCookie) : 0,
            'raw_cookie_hash' => $rawCookie ? substr(md5($rawCookie), 0, 12) : null,
            'resolved_session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'auth_check' => Auth::guard('web')->check(),
            'auth_id' => Auth::guard('web')->id(),
            'response_status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
