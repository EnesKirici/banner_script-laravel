<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQuotesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:500'],
            'overview' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', 'in:movie,tv'],
            'style' => ['nullable', 'string', 'max:500'],
            'regenerate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Film/dizi ID gereklidir.',
            'title.required' => 'Film/dizi adı gereklidir.',
            'overview.required' => 'Film/dizi özeti gereklidir.',
            'type.required' => 'Tür bilgisi gereklidir.',
            'type.in' => 'Tür sadece movie veya tv olabilir.',
        ];
    }
}
