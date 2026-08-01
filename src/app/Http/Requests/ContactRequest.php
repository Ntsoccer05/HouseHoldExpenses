<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class ContactRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'email' => 'required|email:strict,dns|max:255',
            'message' => 'required|string|max:2000',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => ':attributeは必須項目です。',
            'name.max' => ':attributeは:max文字以内で入力してください。',
            'email.required' => ':attributeは必須項目です。',
            'email.email' => '正しいメールアドレスを指定してください。',
            'message.required' => ':attributeは必須項目です。',
            'message.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'message' => 'お問い合わせ内容',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response['status_code'] = 422;
        $response['statusText'] = 'failed Validation';
        $response['errors'] = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }
}
