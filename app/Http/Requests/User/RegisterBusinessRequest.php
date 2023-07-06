<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBusinessRequest extends FormRequest
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
            'business_name' => ['required', 'string', 'max:35', 'min:5'],
            'cac' => ['required', 'string', 'max:45'],
            'rep_name' => ['required', 'string', 'max:45'],
            'rep_position' => ['required', 'string', 'max:45'],
            'phone' => ['required', 'starts_with:234', 'digits:13', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:45', 'unique:users'],
            'referrer_id' => ['sometimes', 'string', 'exists:users,tag', 'bail'],
            'password' => ['required', 'min:8', 'confirmed'],
            'accept_terms' => ['required', 'accepted'],
        ];
    }
}
