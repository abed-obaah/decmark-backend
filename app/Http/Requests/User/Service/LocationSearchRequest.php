<?php

namespace App\Http\Requests\User\Service;

use Illuminate\Foundation\Http\FormRequest;

class LocationSearchRequest extends FormRequest
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
            'coordinate' => ['required', 'array', 'min:2', 'max:2'],
            'coordinate.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'coordinate.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],
        ];
    }
}
