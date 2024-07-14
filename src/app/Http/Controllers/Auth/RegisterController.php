<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\Auth\RegisterRequest;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{

    //ユーザ仮登録処理
    public function temporaryRegister(RegisterRequest $request)
    {

        $user = User::Create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);

        //仮登録処理（メール送信）
        event(new Registered($user));

        return response()->json(["status_code" => 200, "message" => "仮登録しました", "user" => $user, 'email' => $request]);
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

        return response()->json(["status_code" => 200, "message" => "登録しました", "user" => $user, 'email' => $request]);
    }

    

    //Googleユーザ登録処理
    public function registerProviderUser(RegisterRequest $request, string $provider)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'unique:users'],
            'token' => ['required', 'string']
        ]);

        $token = $request->token;

        $providerUser = Socialite::driver($provider)->userFromToken($token);

        $user = User::create([
            'name' => $request->name,
            'email' => $providerUser->getEmail(),
            'password' => null,
        ]);

        Auth::guard()->login($user, true);

        return response()->json(["status_code" => 200, "message" => "登録しました", "user" => $user]);
    }
}