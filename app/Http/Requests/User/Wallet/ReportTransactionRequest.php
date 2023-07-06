<?php

namespace App\Http\Requests\User\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\ValidationRules\Rules\Delimited;

class ReportTransactionRequest extends FormRequest
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
            'transaction' => ['required', 'uuid', 'exists:transactions,id'],
            'flags' => ['required', (new Delimited('alpha:max:15'))->max(10)->min(1)],
            'title' => ['required', 'string', 'max:75'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
