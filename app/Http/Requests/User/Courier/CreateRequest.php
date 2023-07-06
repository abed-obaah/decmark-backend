<?php

namespace App\Http\Requests\User\Courier;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'title' => ['required', 'string',  'min:10', 'max:75'],
            'origin' => ['required', 'array', 'min:2', 'max:2'],
            'origin.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'origin.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'destination' => ['required', 'array', 'min:2', 'max:2'],
            'destination.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'destination.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric'],
        ];
    }
}
