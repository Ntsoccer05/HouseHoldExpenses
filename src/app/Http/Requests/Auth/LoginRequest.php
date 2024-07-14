<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users',
            'password' => 'required|string' 
        ];
    }

    public function messages()
    {
        return [
            'email.required' => ':attributeは必須項目です。',
            'email.email' => '正しいメールアドレスを指定してください。',
            'email.exists' => '入力された:attributeは登録されていません。',
            'password.exists' => ':attributeは必須項目です。',
        ];
    }

    public function attributes()
    {
        return [
            'email' => 'メールアドレス',
            'password' => 'パスワード'
        ];
    }

    protected function failedLoginValidation(Validator $validator){
        $response['status_code'] = 401;
        $response['statusText'] = 'failed Validation';
        $response['errors'] = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json($response, 401)
        );
    }
}