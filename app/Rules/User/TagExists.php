<?php

namespace App\Rules\User;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;

class TagExists implements Rule
{
    /**
     * Request
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($user = User::whereTag($value)->first()) {
            $this->request->merge([
                'user' . ucfirst($attribute) => $user
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'No user found for this :attribute.';
    }
}
