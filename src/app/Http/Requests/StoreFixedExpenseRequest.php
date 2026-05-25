<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => 'required|integer|exists:users,id',
            'category_id'       => 'required|integer',
            'amount'            => 'required|integer|min:1',
            'content'           => 'required|string|max:255',
            'fixed_expense_day' => 'required|integer|min:1|max:31',
        ];
    }
}
