<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailCustomVerificationRequest;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * メールアドレスを認証する
     */
    // __invokeはインスタンス後にメソッド呼出し時に実行される
    public function __invoke(EmailCustomVerificationRequest $request, User $user)
    {

        try{
            $requestUser = $user->findOrFail($request->id);
    
            //メール認証されている時のリダイレクト先
            if ($requestUser->hasVerifiedEmail()) {
                return redirect()->to(config('app.crient_url').'/login');
            }
    
            if ($requestUser->markEmailAsVerified()) {
                event(new Verified($requestUser));
                
                // 認証成功後にログイン
                Auth::guard('web')->login($requestUser);

                // セッション再生成
                $request->session()->regenerate();
            }
    
            $expenseCategory = new ExpenceCategory();
            $incomeCategory = new IncomeCategory();
            DB::transaction(function () use ($expenseCategory, $incomeCategory, $requestUser) {
                $expenseCategory->firstCreateData($requestUser);
                $incomeCategory->firstCreateData($requestUser);
            });
    
            //最終的に任意のルート先にリダイレクトさせるようにします
            return redirect()->to(config('app.crient_url'));
        }catch(Exception $e){
            return redirect()->to(config('app.crient_url').'/login');
        }
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request, User $user)
    {
        $requestUser = $user->where('email', $request->email)->first();
        if ($requestUser->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $requestUser->sendEmailVerificationNotification();

        return response()->json(['message' => '認証用メールを送信しました。ご確認お願いします。']);
    }
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware('signed')->only('verify');
    //     $this->middleware('throttle:6,1')->only('verify', 'resend');
    // }
}