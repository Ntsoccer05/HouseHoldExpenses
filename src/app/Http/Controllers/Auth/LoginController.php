<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
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
            return response()->json(['error' => 'メールアドレス認証がされていません'],403);
        }

        // セッションをログ出力
        // Log::info('セッションID: ' . $request->session()->getId());
        // Log::info('セッション内容: ', $request->session()->all());  // セッションの全データを配列として出力

        // 万が一ログアウトせずセッションが残っていた場合の処理
        $request->session()->invalidate();  // セッションを無効化
        
        // webでログイン guard('web')
        if(Auth::guard('web')->attempt($credentials)){
            $request->session()->regenerate();
            return response()->json(['status_code' => 200,'message' => 'ログインしました'], 200);
        };
    }

    // ログアウト処理
    public function logout(Request $request)
    {
        // ユーザーが認証されていない場合 webでログインしたのでguard('web')
        if ( Auth::guard('web')->guest()) {
            return new JsonResponse([
                'message' => '既にログアウト済みです',
            ]);
        }

        // ログアウトする webでログインしたのでguard('web')
        Auth::guard('web')->logout();
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