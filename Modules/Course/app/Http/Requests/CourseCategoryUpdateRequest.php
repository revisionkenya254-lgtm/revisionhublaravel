<?php

namespace Modules\Course\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'max:255','mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'status' => ['nullable', 'boolean'],
            'code' => ['required', 'exists:languages,code'],
        ];
    }
}
