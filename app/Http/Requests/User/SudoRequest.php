<?php

namespace App\Http\Requests\User;

use App\Enums\SudoTypeEnum;
use App\Rules\User\CurrentPassword;
use Illuminate\Foundation\Http\FormRequest;

class SudoRequest extends FormRequest
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
        if ($this->type === SudoTypeEnum::PASSWORD) {
            return [
                'password' => ['required', new CurrentPassword()]
            ];
        }
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->type = in_array(
            $this->type,
            SudoTypeEnum::values()
        ) ? $this->type : SudoTypeEnum::PASSWORD;
    }
}
