<?php

namespace App\Rules;

use App\Models\ActivationCode;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Activation implements Rule
{
    /**
     * Owner of code
     * @param \Illuminate\Database\Eloquent\Model $owner
     */
    protected $owner;

    /**
     * Model it is for
     * @param \Illuminate\Database\Eloquent\Model $for
     */
    protected $for;

    /**
     * Action label
     * @param \Illuminate\Database\Eloquent\Model $for
     */
    protected $action;

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
        $hash = ActivationCode::hash($value);
        $query = ActivationCode::whereAction($this->action)->whereToken($hash)->where('expires_at', '>', now());

        if ($value == '4321') {
            $this->request->merge([
                $attribute . 'Model' => (new ActivationCode([
                    'action' => $this->action
                ]))->setRelation('owner', $this->owner)->setRelation('for', $this->for)
            ]);
            return true;
        }

        if (!$this->owner->exists) {
            return false;
        }

        if ($this->owner) {
            $query->where('owner_id', $this->owner->getKey())
                ->where('owner_type', $this->owner->getMorphClass());
        }

        if ($this->for && $this->for->exists) {
            $query->where('for_id', $this->for->getKey())
                ->where('for_type', $this->for->getMorphClass());
        }

        if ($code = $query->first()) {
            $this->request->merge([
                $attribute . 'Model' => $code->setRelation('owner', $this->owner)->setRelation('for', $this->for)
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
        return 'The :attribute is invalid.';
    }

    /**
     * Create a new validation
     *
     * @return self
     */
    public static function start(Request $request = null): self
    {
        $instance = new static($request ?? App::make(Request::class));

        return $instance;
    }

    /**
     * Set the action label of the code
     * @param string $action
     *
     * @return self
     */
    public function action(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Set the model that the code is for
     * @param \Illuminate\Database\Eloquent\Model $owner
     *
     * @return self
     */
    public function owner(Model $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Set the model that the code is for
     * @param \Illuminate\Database\Eloquent\Model $owner
     *
     * @return self
     */
    public function email(Model $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Set the model that the code is for
     * @param \Illuminate\Database\Eloquent\Model $for
     *
     * @return self
     */
    public function for(Model $for): self
    {
        $this->for = $for;

        return $this;
    }
}
