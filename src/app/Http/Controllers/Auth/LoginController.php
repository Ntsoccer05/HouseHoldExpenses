<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public $isLogined = false;
     use AuthenticatesUsers;

    // ログイン処理
    public function login(LoginRequest $request)
    {

        // まずLoginRequest.php内でバリデーションされて以下のコードが走る
        $credentials = $request->validated();

        $user = User::where('email', $request->email)->first();
        if(empty($user->email_verified_at)){
            return response()->json(['error' => 'メールアドレス認証がされていません'],401);
        }
        if(Auth::guard('web')->attempt($credentials)){
            $request->session()->regenerate();
            return response()->json(['status_code' => 200,'message' => 'ログインしました'], 200);
        };
    }

    // ログアウト処理
    public function logout(Request $request)
    {
        // ログアウトする
        Auth::guard()->logout();
        // セッションを無効にする
        $request->session()->invalidate();
        // CSRFトークンを再生成する
        $request->session()->regenerateToken();

        return response()->json([
            'status_code' => 200,
            'message' => 'ログアウトしました'
        ], 200);
    }

    // ソーシャルログイン処理
    public function getProviderOAuthURL(string $provider)
    {
        $redirectUrl = Socialite::driver($provider)->redirect()->getTargetUrl();

        return response()->json([
            'redirectUrl' => $redirectUrl
        ]);
    }

    public function handleProviderCallback(Request $request, string $provider)
    {
        $providerUser = Socialite::driver($provider)->stateless()->user();

        $user = User::where('email', $providerUser->getEmail())->first();

        if ($user) {
            Auth::guard()->login($user, true);
            return response()->json([
                'user' => $user
            ]);
        }else{
            return response()->json([
                'provider' => $provider,
                'email' => $providerUser->getEmail(),
                'token' => $providerUser->token,
            ]);
        }
    }
}