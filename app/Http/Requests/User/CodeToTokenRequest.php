<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Rules\Activation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class CodeToTokenRequest extends FormRequest
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
            'code' => ['required', 'string', Activation::start($this)->action($this->action)->owner($this->owner)],
            'action' => ['required', 'string'],
            'email' => ['required', 'email', 'max:45']
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $emptyUser = App::make(User::class);

        $this->merge([
            'owner' => User::whereEmail($this->email)->first() ?? $emptyUser
        ]);
    }
}
