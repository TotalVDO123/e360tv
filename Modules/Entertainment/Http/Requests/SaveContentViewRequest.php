<?php

namespace Modules\Entertainment\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Entertainment\Services\ContentViewService;

class SaveContentViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type' => 'required|string|in:' . implode(',', ContentViewService::allowedContentTypeInputs()),
            'content_id' => 'required|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
            'device_id' => 'nullable|string|max:191',
            'device_type' => 'nullable|string|max:32',
        ];
    }

    public function messages(): array
    {
        return [
            'content_type.required' => 'Invalid content_type or content_id',
            'content_type.in' => 'Invalid content_type or content_id',
            'content_id.required' => 'Invalid content_type or content_id',
            'content_id.integer' => 'Invalid content_type or content_id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = $validator->errors()->first() ?: 'Invalid content_type or content_id';

        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $message,
        ], 422));
    }
}
