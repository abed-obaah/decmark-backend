<?php

namespace App\Services\Payment\Gateway;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Meta implements Countable, Arrayable
{
    /**
     * Meta array
     * @var array[string=>string]
     */
    protected $meta;

    /**
     * Create new Meta
     *
     * @param array $meta
     */
    protected function __construct($meta)
    {
        $this->meta = $meta;
    }

    /**
     * Build Meta object
     *
     * @param array $meta
     * @return static
     */
    public static function data(array $meta = []): static
    {
        $newMeta = [];
        $i = 1;

        foreach ($meta as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(
                    \sprintf('Meta key at %u can only be type string, %s given', $i, gettype($key))
                );
            }

            if (is_object($value) && method_exists($value, '__toString')) {
                $newMeta[$key] = $value->__toString();
            } elseif ($value instanceof Arrayable) {
                $newMeta[$key] = json_encode($value->toArray());
            } elseif (is_string($value)) {
                $newMeta[$key] = $value;
            } elseif (is_numeric($value)) {
                $value = $value;
            } else {
                throw new InvalidArgumentException(\sprintf(
                    'Value at %u can only be type string, %s or implements Class::__toString() magic method, %s given',
                    $i,
                    Arrayable::class,
                    gettype($value)
                ));
            }

            $i++;
        }

        return new static($newMeta);
    }

    /**
     * Add key value pair
     *
     * @param string $key
     * @param int|string|Arrayable $value
     * @return self
     */
    public function add(string $key, $value): self
    {
        /**
         * @method string __toString()
         */
        $value = $value;

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = \app()->call([$value, '__toString']);
        } elseif ($value instanceof Arrayable) {
            $value = json_encode($value->toArray());
        } elseif (is_string($value)) {
            $value = $value;
        } elseif (is_numeric($value)) {
            $value = $value;
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Value can only be of type string, %s or implements Class::__toString() magic method, %s given',
                Arrayable::class,
                gettype($value)
            ));
        }

        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Execute a callback over each product.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->meta as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Count meta
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->meta);
    }

    /**
     * Get the meta values buy key
     *
     * @return string
     */
    public function get(string $key)
    {
        return $this->meta[$key] ?? null;
    }

    /**
     * Get the meta array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->meta;
    }

    /**
     * Get the meta keys
     *
     * @return array
     */
    public function keys(): array
    {
        return \array_keys($this->meta);
    }

    /**
     * Get the meta values
     *
     * @return array
     */
    public function values(): array
    {
        return \array_values($this->meta);
    }

    /**
     * Convert meta to collection
     *
     * @return Collection
     */
    public function collect(): Collection
    {
        return \collect($this->meta);
    }
}
