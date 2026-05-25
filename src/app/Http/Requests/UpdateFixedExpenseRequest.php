<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFixedExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => 'required|integer|exists:users,id',
            'category_id'       => 'sometimes|integer',
            'amount'            => 'sometimes|integer|min:1',
            'content'           => 'sometimes|string|max:255',
            'fixed_expense_day' => 'sometimes|integer|min:1|max:31',
            'is_active'         => 'sometimes|boolean',
        ];
    }
}
