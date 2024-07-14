<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
            'name' => 'nullable|unique:users|string',
            'email' => 'required|unique:users|email:strict,dns,spoof',
            'password' => 'required|string|confirmed' 
        ];
    }

    public function messages()
    {
        return [
            'name.unique'=> ':attributeは既に使用されています。',
            'email.required' => ':attributeは必須項目です。',
            'email.unique' => ':attributeは登録できません。',
            'email.email' => '正しいメールアドレスを指定してください。',
            'password.required' => ':attributeは必須項目です。',
            'password.confirmed' => ':attributeは必須項目です。' 
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'ユーザ名',
            'email' => 'メールアドレス',
            'password' => 'パスワード'
        ];
    }

    protected function failedRegisterValidation(Validator $validator){
        $response['status_code'] = 401;
        $response['statusText'] = 'failed Validation';
        $response['errors'] = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json($response, 401)
        );
    }
}