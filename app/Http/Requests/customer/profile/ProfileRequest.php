<?php

namespace App\Http\Requests\customer\profile;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
        $userId = auth()->user()->id;
        return [
            'f_name' => ['required'],
            'l_name' => ['required'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($userId)],
            'phone' => ['required', Rule::unique('users')->ignore($userId)],
        ];
    }
}
