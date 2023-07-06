<?php

namespace App\Http\Requests\User\Wallet;

use App\Models\BankCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FundWithCardRequest extends FormRequest
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
            'card' => ['required', 'uuid', Rule::exists(BankCard::table(), 'id')
                ->where('owner_id', $this->user()->getKey())
                ->where('owner_type', $this->user()->getMorphClass())],
            'amount' => ['required', 'integer', 'min:10000']
        ];
    }
}
