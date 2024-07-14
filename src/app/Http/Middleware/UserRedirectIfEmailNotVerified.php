<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRedirectIfEmailNotVerified
{
    /**
     * メールアドレスが確認されていない場合は、確認メールを再送信する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // ユーザーがログインしていて、かつemail_verified_atがnullの場合
        if (Auth::check() && Auth::user()->email_verified_at === null) {

            // メールアドレスの確認メールを再送信
            Auth::user()->sendEmailVerificationNotification();

            throw new HttpResponseException(
                response()->json(['message' => 'メール認証されていません。再送したので確認お願いします。'],401)
            );


            return redirect()->to(config('${app.url}/login'));
        }

        return $next($request);
    }
}