<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\Auth\LoginRequest;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
        try{
            // まずLoginRequest.php内でバリデーションされて以下のコードが走る
            $credentials = $request->validated();
    
            $user = User::where('email', $request->email)->first();
            if(empty($user->email_verified_at)){
                return response()->json(['error' => 'メールアドレス認証がされていません'],403);
            }

            // ソーシャルログイン時はパスワードが必要ないため追加バリデーション
            if(!Hash::check($credentials['password'], $user->password)){
                return response()->json(['errors' => ['password'=>['パスワードが間違っています']]],401);
            }
    
            // 万が一ログアウトせずセッションが残っていた場合の処理
            $request->session()->invalidate();  // セッションを無効化
            
            // webでログイン guard('web')
            if (Auth::guard('web')->attempt($credentials)) {
                $request->session()->regenerate();
                return response()->json(['status_code' => 200,'message' => 'ログインしました'], 200);
            };
        }catch(UnauthorizedException $e){
            // 401エラー
            return response()->json(['status_code' => Response::HTTP_UNAUTHORIZED,'message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }catch(Exception $e){
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => 'サーバーエラーが発生しました',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    public function handleProviderCallback(string $provider, Request $request)
    {
        try{
            Log::error(json_encode($provider));
            $providerUser = Socialite::driver($provider)->stateless()->user();
            Log::error(json_encode($providerUser));
            
            $user = User::where('email', $providerUser->email)->first();

            if ($user) {
                Auth::login($user);
            } else {
                $user = User::updateOrCreate([
                    'name' => $providerUser->name,
                    'email' => $providerUser->email,
                    'email_verified_at' => now(),
                    'password' => null,
                ]);
            }
            
            // 万が一ログアウトせずセッションが残っていた場合の処理
            $request->session()->invalidate();

            $initialCategory = $user->IncomeCategories()->get();
            // get() メソッドは、クエリの結果を コレクション として返す。コレクションが空かどうかをチェックするには、Laravel のコレクションメソッド isEmpty() を使用する必要がある
            if($initialCategory->isEmpty()){
                $expenseCategory = new ExpenceCategory();
                $incomeCategory = new IncomeCategory();
                $expenseCategory->firstCreateData($user);
                $incomeCategory->firstCreateData($user);
            }

            // ユーザーを直接ログインさせる
            // Auth::guard('web')->attempt() メソッドは通常、認証情報（email や password）を使用してログインを試みるもの
            Auth::guard('web')->login($user);
            
            $request->session()->regenerate();
            return response()->json(['status_code' => 200,'message' => 'ログインしました'], 200);
        }catch(RequestException  $e){
            return response()->json([
                'provider' => $provider,
            ], 400);
        }
    }
}