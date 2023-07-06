<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Closure;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use InvalidArgumentException;

class GatewayManager
{
    use LabelTrait;

    /**
     * Unresolved driver arrays
     *
     * @var array
     */
    protected $driverResolvers = [];

    /**
     * Resolved driver array
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Load driver to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $driver
     *
     * @return \App\Services\Payment\Gateway\Drivers\DriverInterface|void
     */
    public function driver(string $name, $driver = null)
    {
        if (
            !is_null($driver) &&
            !is_string($driver) &&
            !($driver instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A gateway driver can only be resolved through class name or closure');
        }

        if (!is_null($driver)) {
            if (
                is_string($driver) &&
                !(class_exists($driver) && is_subclass_of($driver, DriverInterface::class))
            ) {
                throw new Exception(sprintf('Gateway driver must implement [%s] interface', DriverInterface::class));
            }

            $this->driverResolvers[$name] = $driver;
        } else {
            return $this->getResolvedDriver($name);
        }
    }

    /**
     * Resolve or get an already resolved driver instance
     *
     * @param string $name
     */
    protected function getResolvedDriver(string $name)
    {
        if (!isset($this->driverResolvers[$name])) {
            throw new Exception(sprintf('"%s" not found as a gateway driver', $name));
        }

        if (!isset($this->drivers[$name])) {
            if (($resolver = $this->driverResolvers[$name]) instanceof \Closure) {
                $driver = $this->resolveDriverFromClosure($resolver);
            } else {
                $driver = $this->resolveDriverFromClass($resolver);
            }

            return $this->drivers[$name] = $driver;
        } else {
            return $this->drivers[$name];
        }
    }

    /**
     * Resolve a driver from closure
     *
     * @param Closure $resolver
     *
     * @return \App\Services\Payment\Gateway\Drivers\DriverInterface
     */
    protected function resolveDriverFromClosure(Closure $resolver): DriverInterface
    {
        if (!($driver = app()->call($resolver)) instanceof DriverInterface) {
            throw new Exception(sprintf('Closure resolver must return an instance of %s', DriverInterface::class));
        }

        return $driver;
    }

    /**
     * Resolve a driver from string
     *
     * @param string $resolver
     *
     * @return \App\Services\Payment\Gateway\Drivers\DriverInterface
     */
    protected function resolveDriverFromClass(string $resolver): DriverInterface
    {
        return app()->make($resolver);
    }

    /**
     * Validation Rule in
     *
     * @return In
     */
    public function ruleIn(): In
    {
        return Rule::in(array_keys($this->driverResolvers));
    }

    /**
     * Process a transaction with it`s label
     *
     * @param LabelInterface $label
     * @param Transaction $transaction
     * @return void
     */
    public function processLabel(LabelInterface $label, Transaction $transaction)
    {
        switch ($transaction->status()) {
            case Transaction::SUCCESS:
                $label->success($transaction);
                break;
            case Transaction::FAILED:
                $label->failed($transaction);
                break;
            case Transaction::REVERSED:
                $label->reversed($transaction);
                break;
            case Transaction::PROCESSING:
                $label->processing($transaction);
                break;
        }
    }

    /**
     * Process a transaction with it`s label
     *
     * @param LabelInterface $label
     * @param Refund $refund
     * @return void
     */
    public function processRefund(LabelInterface $label, Refund $refund)
    {
        $label->refund($refund);
    }
}
