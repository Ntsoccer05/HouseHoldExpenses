<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\Auth\SignupVerify;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{

    //ユーザ仮登録処理
    public function temporaryRegister(RegisterRequest $request)
    {
        try{

            $user = User::Create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
            ]);
    
            // 仮登録メール送信
            // Illuminate\Auth\Events\Registered eventは使わない。本登録用URLをSPA側URLにする独自実装をしたいという都合のため。
            // $verificationUrl = $this->createVerificationUrl($user);
            // Log::error($verificationUrl);
            // $user->notify(new SignupVerify($verificationUrl));
            
            //仮登録処理（メール送信）webガードでしか実装できない
            event(new Registered($user));
    
            return response()->json(["status_code" => 200, "message" => "仮登録しました", "user" => $user, 'email' => $request]);
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }


    //ユーザ本登録処理
    public function formatRegister(Request $request)
    {
        Validator::make($request, [
            'email' => 'required|string|email|max:255|unique:users|email:strict,dns,spoof',
            'token' => 'required|string',
        ])->validate();

        $user = User::where('email', $request->email)->first();
        $user->email_verified_at = Carbon::now();
        $user->save();
        if (Auth::guard('web')->attempt([$user->email, $user->password])) {
            $request->session()->regenerate();
            return response()->json(['status_code' => 200,'message' => 'ログインしました'], 200);
        };

        return response()->json(["status_code" => 200, "message" => "登録しました", "user" => $user, 'email' => $request]);
    }

    

    //Googleユーザ登録処理
    public function registerProviderUser(RegisterRequest $request, string $provider)
    {
        DB::beginTransaction();
        try{
            $providerUser = Socialite::driver($provider)->user();
    
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
                Auth::login($user);
            }
    
            // Sanctum トークンを生成
            $token = $user->createToken('google-login')->plainTextToken;
    
            $initialCategory = $user->IncomeCategories()->get();
            // get() メソッドは、クエリの結果を コレクション として返す。コレクションが空かどうかをチェックするには、Laravel のコレクションメソッド isEmpty() を使用する必要がある
            if($initialCategory->isEmpty()){
                $expenseCategory = new ExpenceCategory();
                $incomeCategory = new IncomeCategory();
                $expenseCategory->firstCreateData($user);
                $incomeCategory->firstCreateData($user);
            }
            DB::commit();
            // ユーザーとトークンを返す
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        }catch(Exception $e){
            DB::rollBack();
        }
    }
}