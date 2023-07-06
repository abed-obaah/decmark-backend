<?php

namespace App\Http\Requests\User\Courier;

use Illuminate\Foundation\Http\FormRequest;

class SearchCourierRequest extends FormRequest
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
            'depature_point' => ['required', 'array', 'min:2', 'max:2'],
            'depature_point.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'depature_point.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],

            'arrival_point' => ['required', 'array', 'min:2', 'max:2'],
            'arrival_point.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'arrival_point.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],
        ];
    }
}
