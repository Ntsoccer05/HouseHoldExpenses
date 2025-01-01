<?php

namespace App\Http\Requests;

use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Fetch all categories using the models
        $expenseCategories = ExpenceCategory::pluck('content')->toArray();
        $incomeCategories = IncomeCategory::pluck('content')->toArray();

        // Merge both arrays to create a complete list of valid categories
        $validCategories = array_merge($expenseCategories, $incomeCategories);

        return [
            'transaction.type' => 'required|in:expense,income',
            'transaction.date' => 'required|string',
            'transaction.amount' => 'required|numeric|min:1',
            'transaction.content' => 'nullable|string|max:50',
            'transaction.category' => ['required', Rule::in($validCategories)],
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