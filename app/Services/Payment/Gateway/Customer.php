<?php

namespace App\Services\Payment\Gateway;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use ReflectionClass;

class Customer implements Arrayable
{
    /**
     * Email id type
     *
     * @var string
     */
    public const TYPE_EMAIL = 'TYPE_EMAIL';

    /**
     * Phone id type
     *
     * @var string
     */
    public const TYPE_PHONE = 'TYPE_PHONE';

    /**
     * Product id
     *
     * @var string
     */
    protected $id;

    /**
     * Product type
     *
     * @var string
     */
    protected $type;

    /**
     * Product name
     *
     * @var string
     */
    protected $name;

    /**
     * Product email
     *
     * @var string
     */
    protected $email;

    /**
     * Product phone
     *
     * @var string
     */
    protected $phone;

    public function __construct(
        string $id,
        string $type,
        string $name,
        string $email,
        string $phone,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;

        $types = \array_keys((new ReflectionClass(self::class))->getConstants());

        if (!in_array($type, $types)) {
            throw new Exception(\sprintf('Customer ID type must be in [%s]', \implode(',', $types)));
        }

        $this->type = $type;
    }

    /**
     * Get customer id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Check type
     *
     * @param string $type
     * @return boolean
     */
    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * Get customer phone
     *
     * @return string
     */
    public function phone(): string
    {
        return $this->phone;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }
}
