<?php

namespace App\Http\Requests\User;

use App\Rules\User\CurrentPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', new CurrentPassword()],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
