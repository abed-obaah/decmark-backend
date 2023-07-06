<?php

namespace App\Services\Payment\Gateway;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Walletable\Money\Money;

class Products implements Countable, Arrayable
{
    /**
     * Product collection
     * @var array[int=>Product]
     */
    protected $products;

    /**
     * Create new Products
     *
     * @param Product ...$products
     */
    public function __construct(Product ...$products)
    {
        $this->products = $products;
    }

    /**
     * Execute a callback over each product.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->products as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Count products
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->products);
    }

    /**
     * Products array
     *
     * @return array
     */
    public function get(): array
    {
        return $this->products;
    }

    /**
     * Get the total amount
     *
     * @return Money
     */
    public function amount(): Money
    {
        return Money::sum(...\collect($this->products)->reduce(function ($result, $product) {
            $result[] = $product->price()->multiply($product->quantity());
            return $result;
        }, []));
    }

    /**
     * Get the products as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return \collect($this->products)->reduce(function ($result, $item) {
            $result[] = $item->toArray();
            return $result;
        }, []);
    }

    /**
     * Get the first item from the collection passing the given truth test.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return ?Product
     */
    public function first(callable $callback = null, $default = null): ?Product
    {
        return Arr::first($this->products, $callback, $default);
    }

    /**
     * Convert products to collection
     *
     * @return Collection
     */
    public function collect(): Collection
    {
        return \collect($this->products);
    }

    /**
     * Eager Load models
     *
     * @return self
     */
    public function withModel(): self
    {
        $group = ($collection = $this->collect())->mapToGroups(function (Product $product) {
            return $product->hasModel() ?
                [$product->modelType() => $product] : [];
        });

        $models = \collect([]);

        $group->each(function (Collection $products, $key) use ($models) {
            if (!($classExists = class_exists($key)) && !($morphClass = Relation::getMorphedModel($key))) {
                return;
            }

            $class = $classExists ? $key : $morphClass;

            $ids = $products->reduce(function ($results, Product $product) {
                $results[] = $product->modelId();
                return $results;
            }, []);

            $keyName = (new $class())->getKeyName();

            $class::query()->whereIn($keyName, $ids)->get()
                ->each(function ($model) use ($key, $models) {
                    $models[$model->getKey() . $key] = $model;
                });
        });

        $collection->each(function (Product $product) use ($models) {
            if ($model = $models->get($product->modelId() . $product->modelType())) {
                $product->setModel($model);
            }
        });

        return $this;
    }
}
