<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        if ($this->handle_type === 'email') {
            $handle = [
                'handle' => 'required|email',
            ];
        } else {
            $handle = [
                'handle' => 'required|digits:13',
            ];
        }
        return [
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (filter_var($this->handle, FILTER_VALIDATE_EMAIL)) {
            $this->handle_type = 'email';
        } elseif (is_numeric(request()->handle)) {
            $this->handle_type = 'phone';
        } else {
            $this->handle_type = 'email';
        }
    }
}
