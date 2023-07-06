<?php

namespace App\Http\Requests\User;

use App\Enums\GenderEnum;
use App\Enums\ReligionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'first_name' => ['sometimes', 'string', 'min:3', 'max:45'],
            'last_name' => ['sometimes', 'string', 'min:3', 'max:45'],
            'image' => ['sometimes', 'image', 'mimes:jpg,jpeg,bmp,png', 'max:10240'],
            'profile_img' => ['sometimes'],
            'middle_name' => ['sometimes', 'string', 'min:3', 'max:45'],
            'gender' => ['sometimes', Rule::in(GenderEnum::values())],
            'occupation' => ['sometimes', 'string', 'min:3', 'max:45'],
            'state' => ['sometimes', 'string', 'min:3', 'max:45'],
            'city' => ['sometimes', 'string', 'min:3', 'max:45'],
            'address' => ['sometimes', 'string', 'min:3', 'max:200'],
            'home_description' => ['sometimes', 'string', 'min:3', 'max:400'],
            'date_of_birth' => ['sometimes', 'string', 'date', 'date_format:Y-m-d'],
            'next_of_kin_name' => ['sometimes', 'string', 'min:3', 'max:75'],
            'next_of_kin_relationship' => ['sometimes', 'string', 'min:3', 'max:45'],
            'next_of_kin_address' => ['sometimes', 'string', 'min:3', 'max:200'],
            'next_of_kin_email' => ['sometimes', 'email', 'max:75'],
            'next_of_kin_phone' => ['sometimes', 'string', 'min:10', 'max:20'],
        ];
    }
}
