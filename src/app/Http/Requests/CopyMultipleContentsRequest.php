<?php

namespace App\Http\Requests;

use App\Models\Content;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CopyMultipleContentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if all content_ids belong to the current user
        $contentIds = $this->input('content_ids', []);

        if (empty($contentIds)) {
            return false;
        }

        // Verify that all content_ids belong to the current user
        $userContentCount = Content::where('user_id', auth()->id())
            ->whereIn('id', $contentIds)
            ->count();

        return count($contentIds) === $userContentCount;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'source_date' => 'required|date_format:Y-m-d',
            'destination_date' => 'required|date_format:Y-m-d',
            'content_ids' => 'required|array|min:1',
            'content_ids.*' => 'required|integer|exists:contents,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'source_date.required' => 'コピー元の日付は必須です',
            'source_date.date_format' => 'コピー元の日付形式が正しくありません（YYYY-MM-DD）',
            'destination_date.required' => 'コピー先の日付は必須です',
            'destination_date.date_format' => 'コピー先の日付形式が正しくありません（YYYY-MM-DD）',
            'content_ids.required' => 'コピー対象の支出を選択してください',
            'content_ids.array' => 'コピー対象は配列である必要があります',
            'content_ids.min' => '最低1件の支出を選択してください',
            'content_ids.*.integer' => 'コピー対象の支出IDは整数である必要があります',
            'content_ids.*.exists' => 'コピー対象の支出が見つかりません',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $response['status_code'] = 422;
        $response['statusText'] = 'Failed validation.';
        $response['errors'] = $validator->errors()->toArray();

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }
}
