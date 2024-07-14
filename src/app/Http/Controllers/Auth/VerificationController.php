<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailCustomVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\RedirectResponse;

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
    // protected $redirectTo = '/home';

    /**
     * メールアドレスを認証する
     */
    // __invokeはインスタンス後にメソッド呼出し時に実行される
    public function __invoke(EmailCustomVerificationRequest $request, User $user): RedirectResponse
    {

        $requestUser = $user->find($request->id);

        //メール認証されている時のリダイレクト先
        if ($requestUser->hasVerifiedEmail()) {
            return redirect()->to(config('app.url'));
        }

        if ($requestUser->markEmailAsVerified()) {
            event(new Verified($requestUser));
        }

        //最終的に任意のルート先にリダイレクトさせるようにします
        return redirect()->to(config('app.url'));
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