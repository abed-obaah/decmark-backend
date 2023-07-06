<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Rules\JWT;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', JWT::check(function (array $data) {
                return isset($data['user_id']) && User::whereId($data['user_id'])->count() &&
                isset($data['action']) && $data['action'] === 'reset_password' &&
                isset($data['expires_at']) && Date::parse($data['expires_at'])->gt(now());
            })],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
