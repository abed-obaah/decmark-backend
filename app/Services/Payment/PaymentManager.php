<?php

namespace App\Services\Payment;

use App\Services\Payment\Methods\PaymentMethod;
use Closure;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Unresolved method arrays
     *
     * @var array
     */
    protected $methodResolvers = [];

    /**
     * Resolved method array
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Validation Rule in
     *
     * @return In
     */
    public function ruleIn(): In
    {
        return Rule::in(array_keys($this->methodResolvers));
    }

    /**
     * Load method to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $method
     *
     * @return \App\Services\Payment\Methods\PaymentMethod|void
     */
    public function method(string $name, $method = null)
    {
        if (
            !is_null($method) &&
            !is_string($method) &&
            !($method instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A payment method can only be resolved through class name or closure');
        }

        if (!is_null($method)) {
            if (
                is_string($method) &&
                !(class_exists($method) && is_subclass_of($method, PaymentMethod::class))
            ) {
                throw new Exception(sprintf('Payment method must implement [%s] interface', PaymentMethod::class));
            }

            $this->methodResolvers[$name] = $method;
        } else {
            return $this->getResolvedDriver($name);
        }
    }

    /**
     * Resolve or get an already resolved method instance
     *
     * @param string $name
     */
    protected function getResolvedDriver(string $name)
    {
        if (!isset($this->methodResolvers[$name])) {
            throw new Exception(sprintf('"%s" not found as a payment method', $name));
        }

        if (!isset($this->methods[$name])) {
            if (($resolver = $this->methodResolvers[$name]) instanceof \Closure) {
                $method = $this->resolveDriverFromClosure($resolver);
            } else {
                $method = $this->resolveDriverFromClass($resolver);
            }

            return $this->methods[$name] = $method;
        } else {
            return $this->methods[$name];
        }
    }

    /**
     * Resolve a method from closure
     *
     * @param Closure $resolver
     *
     * @return \App\Services\Payment\Methods\PaymentMethod
     */
    protected function resolveDriverFromClosure(Closure $resolver): PaymentMethod
    {
        if (!($method = app()->call($resolver)) instanceof PaymentMethod) {
            throw new Exception(sprintf('Closure resolver must return an instance of %s', PaymentMethod::class));
        }

        return $method;
    }

    /**
     * Resolve a method from string
     *
     * @param string $resolver
     *
     * @return \App\Services\Payment\Methods\PaymentMethod
     */
    protected function resolveDriverFromClass(string $resolver): PaymentMethod
    {
        return app()->make($resolver);
    }
}
