<?php

namespace App\Http\Requests\User\Service;

use App\Enums\ServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'coordinate' => ['sometimes', 'array', 'min:2', 'max:2'],
            'coordinate.0' => ['sometimes', 'numeric', 'between:-180.000000,180.000000'],
            'coordinate.1' => ['sometimes', 'numeric', 'between:-180.000000,180.000000'],
            'title' => ['sometimes', 'string', 'min:10', 'max:75'],
            'type' => ['sometimes', Rule::in(ServiceTypeEnum::values())],
            'price' => ['sometimes', 'numeric'],
            'description' => ['sometimes', 'max:2000'],
            'duration' => ['sometimes', 'numeric'],
        ];
    }
}
