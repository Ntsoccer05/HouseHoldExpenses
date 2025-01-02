<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as Middleware;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;

// src/vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.phpをカスタム
class CustomEnsureEmailsVerified extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        $requestUser = User::where('email', $request->email)->first();
        if (! $requestUser ||
            ($requestUser instanceof MustVerifyEmail &&
            ! $requestUser->hasVerifiedEmail())) {
            return abort(403, 'メールアドレスは認証されていません。');
        }
        $response = $next($request);

        return $response;
    }
}