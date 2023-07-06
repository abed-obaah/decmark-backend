<?php

namespace App\Services\Payment\Gateway;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Walletable\Money\Money;

class Product implements Arrayable
{
    /**
     * Product name
     *
     * @var string
     */
    protected $name;

    /**
     * Product price
     *
     * @var Money
     */
    protected $price;

    /**
     * Product Quantity
     *
     * @var int
     */
    protected $quantity;

    /**
     * Product model id
     *
     * @var string|int
     */
    protected $model_id = null;

    /**
     * Product model type
     *
     * @var string
     */
    protected $model_type = null;

    /**
     * Product model instance
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    public function __construct(string $name, Money $price, int $quantity, Model $model = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
        if (!is_null($model)) {
            $this->setModel($model);
        }
    }

    /**
     * Set model instance
     *
     * @param Model $model
     * @return self
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        $this->model_id = $model->getKey();
        $this->model_type = $model->getMorphClass();

        return $this;
    }

    public function setModelMorph(string $model_id, string $model_type): self
    {
        $this->model_id = $model_id;
        $this->model_type = $model_type;

        return $this;
    }

    /**
     * Get product model
     *
     * @return ?Model
     */
    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * Check if product can load a model
     *
     * @return bool
     */
    public function hasModel(): bool
    {
        return !is_null($this->model_id) && !is_null($this->model_type);
    }

    /**
     * Check if product model is loaded
     *
     * @return bool
     */
    public function modelLoaded(): bool
    {
        return !is_null($this->model);
    }

    /**
     * Load model
     *
     * @return self
     */
    public function loadModel(): self
    {
        if (class_exists($this->model_type)) {
            $this->model = $this->model_type::find($this->model_id);
        } elseif ($class = Relation::getMorphedModel($this->model_type)) {
            $this->model = $class::find($this->model_id);
        }

        return $this;
    }

    /**
     * Get product model id
     *
     * @return string|int
     */
    public function modelId(): null|string|int
    {
        return $this->model_id;
    }

    /**
     * Get product model type
     *
     * @return string
     */
    public function modelType(): ?string
    {
        return $this->model_type;
    }

    /**
     * Get product name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get product price
     *
     * @return Money
     */
    public function price(): Money
    {
        return $this->price;
    }

    /**
     * Get product quantity
     *
     * @return int
     */
    public function quantity(): int
    {
        return $this->quantity;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $data = [
            'name' => $this->name,
            'price' => $this->price->getInt(),
            'quantity' => $this->quantity
        ];

        if (!is_null($this->model_id) && !is_null($this->model_type)) {
            $data = array_merge($data, [
                'model_id' => is_object($this->model_id) ? (string)$this->model_id : $this->model_id,
                'model_type' => $this->model_type
            ]);
        }

        return $data;
    }
}
