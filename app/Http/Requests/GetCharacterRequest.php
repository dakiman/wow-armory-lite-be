<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetCharacterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'region' => ['required', 'string', Rule::in(config('blizzard.regions'))],
            'realm' => ['required', 'string', 'max:255'],
            'characterName' => ['required', 'string', 'max:255'],
            'isClassic' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'region' => $this->route('region'),
            'realm' => $this->route('realm'),
            'characterName' => $this->route('characterName'),
            'isClassic' => filter_var($this->query('isClassic', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
