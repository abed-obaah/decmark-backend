<?php

namespace App\Http\Requests\User\Service;

use App\Enums\ServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'coordinate' => ['required', 'array', 'min:2', 'max:2'],
            'coordinate.0' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'coordinate.1' => ['required', 'numeric', 'between:-180.000000,180.000000'],
            'title' => ['required', 'string', 'min:10', 'max:75'],
            'type' => ['required', Rule::in(ServiceTypeEnum::values())],
            'price' => ['required', 'numeric'],
            'description' => ['nullable', 'max:2000'],
            'duration' => ['required', 'numeric'],
            'attachments' => 'required|array',
        ];
    }
}
