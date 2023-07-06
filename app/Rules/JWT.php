<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class JWT implements Rule
{
    /**
     * The decrypted data
     * @var array
     */
    protected $decrypted;

    /**
     * The token validator
     * @var \Closure
     */
    protected $validator;

    /**
     * The token validator
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected function __construct(Request $request)
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
        if ($this->validate(($value))) {
            $this->request->merge([
                $attribute . 'Decrypted' => $this->decrypted
            ]);

            return true;
        }

        return false;
    }

    /**
     * Determine if the JWT token is valid.
     *
     * @param  string  $value
     * @return bool
     */
    public function validate(string $token)
    {
        try {
            $decrypted = json_decode(Crypt::decryptString($token), true);
        } catch (Throwable $e) {
            return false;
        }

        if (call_user_func($this->validator, $decrypted)) {
            $this->decrypted = $decrypted;
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
        return 'The :attribute is not a valid token.';
    }

    /**
     * Create an instance with a closure as validator.
     * @param $closure
     */
    public static function check(Closure $closure)
    {
        $instance = new static(App::make(Request::class));
        $instance->validator = $closure;

        return $instance;
    }
}
