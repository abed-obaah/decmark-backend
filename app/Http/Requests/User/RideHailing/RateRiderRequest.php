<?php

namespace App\Http\Requests\User\RideHailing;

use Illuminate\Foundation\Http\FormRequest;

class RateRiderRequest extends FormRequest
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
            'rider_id' => 'required',
            'rating' => 'required',
            'review' => 'required|max:255',
        ];
    }
}
