<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Walletable\Money\Money;

/**
 * Payment instance traits
 */
trait PaymentTrait
{
    /**
     * Payment driver
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Payment reference
     *
     * @var string
     */
    protected $reference;

    /**
     * Amount to pay
     *
     * @var Money
     */
    protected $amount;

    /**
     * Amount charged
     *
     * @var Money
     */
    protected $charge;

    /**
     * Amount to pay
     *
     * @var Customer
     */
    protected $customer;

    /**
     * Amount to pay
     *
     * @var Products
     */
    protected $products;

    /**
     * Amount to pay
     *
     * @var Meta
     */
    protected $meta;

    /**
     * Get checkout reference
     *
     * @return string
     */
    public function reference(): string
    {
        return $this->reference;
    }

    /**
     * Get checkout amount
     *
     * @return Money
     */
    public function amount(): Money
    {
        return $this->amount;
    }

    /**
     * Get checkout charge
     *
     * @return Money
     */
    public function charge(): Money
    {
        return $this->charge;
    }

    /**
     * Get checkout customer
     *
     * @return Customer
     */
    public function customer(): Customer
    {
        return $this->customer;
    }

    /**
     * Get checkout products
     *
     * @return Products
     */
    public function products(): Products
    {
        return $this->products;
    }

    /**
     * Get checkout meta
     *
     * @return Meta
     */
    public function meta(): Meta
    {
        return $this->meta;
    }

    /**
     * Get checkout driver
     *
     * @return DriverInterface
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }
}
