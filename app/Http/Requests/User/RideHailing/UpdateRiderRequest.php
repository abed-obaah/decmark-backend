<?php

namespace App\Http\Requests\User\RideHailing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderRequest extends FormRequest
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
            'current_coordinate_lat' => 'required|between:-180.000000,180.000000',
            'current_coordinate_long' => 'required|between:-180.000000,180.000000',
        ];
    }
}
