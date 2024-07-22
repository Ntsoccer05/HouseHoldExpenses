<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'email' => 'required|email|exists:users',
            'token' => 'required',
            'password' => 'required|min:4|confirmed',
        ];
    }

    public function messages()
    {
        // :attributeはemailはメールアドレス、passwordはパスワードとなる
        // :minは４が入る
        return [
            'email.required' => ':attributeは必ず指定してください。',
            'email.email' => '正しい:attributeを指定してください。',
            'email.exists' => '入力された:attributeは登録されていません。',
            'password.required' => ':attributeは必ず指定してください。',
            'password.confirmed' => ':attributeと、確認用パスワードが一致していません。',  
            'password.min' => ':attributeは:min文字以上で指定してください。',  
        ];
    }

    public function attributes()
    {
        return [
            'email' => 'メールアドレス',
            'password' => 'パスワード'
        ];
    }

    // 関数名はリクエスト毎でユニークにする
    protected function failedResetPasswordValidation(Validator $validator) {
        $response['status_code']  = 401;
        $response['statusText'] = 'Failed validation.';
        $response['errors']  = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json( $response, 401 )
        );
    }
}