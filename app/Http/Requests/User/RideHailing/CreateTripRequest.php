<?php

namespace App\Http\Requests\User\RideHailing;

use Illuminate\Foundation\Http\FormRequest;

class CreateTripRequest extends FormRequest
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
            'current_location_coordinate_lat' => 'required|between:-180.000000,180.000000',
            'current_location_coordinate_long' => 'required|between:-180.000000,180.000000',
            'destination_coordinate_lat' => 'required|between:-180.000000,180.000000',
            'destination_coordinate_long' => 'required|between:-180.000000,180.000000',
            'ride_type' => 'required',
            'passengers_count' => 'required|integer|max:3|min:1',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s|after:today'
        ];
    }
}
