<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSplitGroupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_other_ratio'            => 'nullable|integer|min:0|max:100',
            'income_other_offset'           => 'nullable|integer',
            'expense_other_ratio'           => 'nullable|integer|min:0|max:100',
            'expense_other_offset'          => 'nullable|integer',
            'overrides'                     => 'nullable|array',
            'overrides.*.category_id'       => 'required|integer',
            'overrides.*.type_id'           => 'required|integer',
            'overrides.*.other_ratio'       => 'required|integer|min:0|max:100',
        ];
    }
}
