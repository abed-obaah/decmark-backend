<?php

namespace App\Http\Requests\User\Wallet;

use App\Rules\SpendingAmount;
use App\Rules\User\CurrentPin;
use App\Rules\User\TagExists;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'tag' => ['required', new TagExists($this)],
            'amount' => ['required', new SpendingAmount()],
            'remarks' => ['required', 'string', 'min:10', 'max:200'],
            'pin' => ['required', new CurrentPin()],
            'beneficiary' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->amount) {
            $this->amount = (int)$this->amount;
        }
    }
}
