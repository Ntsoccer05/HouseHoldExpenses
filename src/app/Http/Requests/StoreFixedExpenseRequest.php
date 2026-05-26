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
            'type'              => 'required|in:income,expense',
            'category_id'       => 'required|integer',
            'amount'            => 'required|integer|min:1',
            'content'           => 'nullable|string|max:255',
            'fixed_expense_day' => 'required|integer|min:1|max:31',
        ];
    }
}
