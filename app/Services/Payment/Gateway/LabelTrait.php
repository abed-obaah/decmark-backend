<?php

namespace App\Services\Payment\Gateway;

use Closure;
use Exception;
use InvalidArgumentException;

/**
 * Gateway manager label trait
 */
trait LabelTrait
{
    /**
     * Unresolved label arrays
     *
     * @var array
     */
    protected $labelResolvers = [];

    /**
     * Resolved label array
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Load label to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $label
     *
     * @return \App\Services\Payment\Gateway\LabelInterface|void
     */
    public function label(string $name, $label = null)
    {
        if (
            !is_null($label) &&
            !is_string($label) &&
            !($label instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A gateway label can only be resolved through class name or closure');
        }

        if (!is_null($label)) {
            if (
                is_string($label) &&
                !(class_exists($label) && is_subclass_of($label, LabelInterface::class))
            ) {
                throw new Exception(sprintf('Gateway label must implement [%s] interface', LabelInterface::class));
            }

            $this->labelResolvers[$name] = $label;
        } else {
            return $this->getResolvedLabel($name);
        }
    }

    /**
     * Resolve or get an already resolved label instance
     *
     * @param string $name
     */
    protected function getResolvedLabel(string $name)
    {
        if (!isset($this->labelResolvers[$name])) {
            throw new Exception(sprintf('"%s" not found as a gateway label', $name));
        }

        if (!isset($this->labels[$name])) {
            if (($resolver = $this->labelResolvers[$name]) instanceof \Closure) {
                $label = $this->resolveLabelFromClosure($resolver);
            } else {
                $label = $this->resolveLabelFromClass($resolver);
            }

            return $this->labels[$name] = $label;
        } else {
            return $this->labels[$name];
        }
    }

    /**
     * Resolve a label from closure
     *
     * @param Closure $resolver
     *
     * @return \App\Services\Payment\Gateway\LabelInterface
     */
    protected function resolveLabelFromClosure(Closure $resolver): LabelInterface
    {
        if (!($label = app()->call($resolver)) instanceof LabelInterface) {
            throw new Exception(sprintf('Closure resolver must return an instance of %s', LabelInterface::class));
        }

        return $label;
    }

    /**
     * Resolve a label from string
     *
     * @param string $resolver
     *
     * @return \App\Services\Payment\Gateway\LabelInterface
     */
    protected function resolveLabelFromClass(string $resolver): LabelInterface
    {
        return app()->make($resolver);
    }
}
