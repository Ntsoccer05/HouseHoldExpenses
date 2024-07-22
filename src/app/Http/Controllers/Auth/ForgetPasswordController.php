<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password; //←パスワードリセットの通知メールを送る処理に必要
use Illuminate\Auth\Events\PasswordReset; //←パスワードリセットDB更新処理に必要
use Illuminate\Support\Facades\Hash; //←パスワードリセットDB更新処理に必要
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; //←パスワードリセットDB更新処理に必要

class ForgetPasswordController extends Controller
{
    //
    /**
     * パスワードリセットの通知メール送信
     * ↓↓公式のリファレンスをそのまま参照↓↓
     * https://readouble.com/laravel/8.x/ja/passwords.html
    */
    public function sendemail(ForgetPasswordRequest $request){

        Log::error($request);
        $request->validated();

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
    }

    /**
     * パスワードリセットのフォームのビューで使うtokenを返す。
     * ↓↓公式のリファレンスを変更し、SAP用にviewを返さないように改変↓↓
     * https://readouble.com/laravel/8.x/ja/passwords.html
     * SPAではいらない？？
    */
    public function resetform($token){
        return response()->json([
            'token' => $token,
        ]);
    }


    /**
     * パスワードリセット処理(DBのパスワード更新)
     * ↓↓公式のリファレンスをそのまま参照↓↓
     * https://readouble.com/laravel/8.x/ja/passwords.html
    */
    public function passwordreset(ResetPasswordRequest $request){
        $request->validated();

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
    
                $user->save();
    
                event(new PasswordReset($user));
            }
        );
    
        //ここの”->route('***')”の***部分でルート名の指定がないとエラー出る。デフォは'login'になってるので
        //アプリケーションの仕様に合わせて変更すること
        return $status === Password::PASSWORD_RESET ? redirect()->to('/')->with('status', __($status)) : back()->withErrors(['email' => [__($status)]]);
    }
}