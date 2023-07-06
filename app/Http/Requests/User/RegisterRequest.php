<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'max:35'],
            'last_name' => ['required', 'string', 'max:35'],
            'gender' => ['required', 'string', 'max:35', 'in:male,female,null'],
            'phone' => ['required', 'starts_with:234', 'digits:13', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:45', 'unique:users'],
            'referrer_id' => ['sometimes', 'string', 'exists:users,tag'],
            'password' => ['required', 'min:8', 'confirmed'],
            'accept_terms' => ['required', 'accepted'],
        ];
    }
}
