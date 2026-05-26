<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryOverridesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overrides'               => 'present|array',
            'overrides.*.category_id' => 'required|integer|min:1',
            'overrides.*.type_id'     => 'required|integer|in:1,2',
            'overrides.*.other_ratio' => 'required|integer|min:0|max:100',
        ];
    }
}
