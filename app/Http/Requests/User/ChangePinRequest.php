<?php

namespace App\Http\Requests\User;

use App\Rules\User\CurrentPin;
use Illuminate\Foundation\Http\FormRequest;

class ChangePinRequest extends FormRequest
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
            'current_pin' => ['required', new CurrentPin()],
            'pin' => ['required', 'digits:4', 'confirmed']
        ];
    }
}
