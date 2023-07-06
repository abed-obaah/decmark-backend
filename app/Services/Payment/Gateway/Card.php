<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Illuminate\Contracts\Support\Arrayable;

class Card implements Arrayable
{
    /**
     * Payment driver
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Card number
     *
     * @var string
     */
    protected $number;

    /**
     * Card brand
     *
     * @var string
     */
    protected $brand;

    /**
     * Card expiry month
     *
     * @var string
     */
    protected $expiry_month;

    /**
     * Card expiry year
     *
     * @var string
     */
    protected $expiry_year;

    /**
     * Card name
     *
     * @var string
     */
    protected $name;

    /**
     * Card bank name
     *
     * @var string
     */
    protected $bank;

    /**
     * Card country
     *
     * @var string
     */
    protected $country_code;

    /**
     * Card authorization
     *
     * @var string
     */
    protected $authorization;

    public function __construct(
        DriverInterface $driver,
        string $number,
        string $brand,
        string $expiry_month,
        string $expiry_year,
        string $name,
        string $bank,
        string $country_code,
        string $authorization = null,
    ) {
        $this->driver = $driver;
        $this->number = $number;
        $this->brand = $brand;
        $this->expiry_month = $expiry_month;
        $this->expiry_year = $expiry_year;
        $this->name = $name;
        $this->bank = $bank;
        $this->country_code = $country_code;
        $this->authorization = $authorization;
    }

    /**
     * Get card bin
     *
     * @return string
     */
    public function bin(): string
    {
        return substr($this->number, 0, 6);
    }

    /**
     * Get card number
     *
     * @return string
     */
    public function number(): string
    {
        return $this->number;
    }

    /**
     * Get card last four digits
     *
     * @return string
     */
    public function lastFour(): string
    {
        return substr($this->number, -4);
    }

    /**
     * Get card brand
     *
     * @return string
     */
    public function brand(): string
    {
        return $this->brand;
    }

    /**
     * Get card expiry month
     *
     * @return string
     */
    public function expiryMonth(): string
    {
        return $this->expiry_month;
    }

    /**
     * Get card expiry year
     *
     * @return string
     */
    public function expiryYear(): string
    {
        return $this->expiry_year;
    }

    /**
     * Get card expiry date with slash
     *
     * @return string
     */
    public function expiry(): string
    {
        return $this->expiry_month . '/' . ((strlen($this->expiry_year) > 2) ?
            substr($this->expiry_year, -2) : $this->expiry_year);
    }

    /**
     * Get card name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get card bank
     *
     * @return string
     */
    public function bank(): string
    {
        return $this->bank;
    }

    /**
     * Get card country code
     *
     * @return string
     */
    public function countryCode(): string
    {
        return $this->country_code;
    }

    /**
     * Get card authorization
     *
     * @return string
     */
    public function authorization(): string
    {
        return $this->authorization;
    }

    /**
     * Check if card is authorized
     *
     * @return bool
     */
    public function authorized(): bool
    {
        return !is_null($this->authorization) && !empty($this->authorization);
    }

    /**
     * Get gateway driver
     *
     * @return DriverInterface
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'bin' => $this->bin,
            'number' => $this->number,
            'last_four' => $this->last_four,
            'brand' => $this->brand,
            'expiry_month' => $this->expiry_month,
            'expiry_year' => $this->expiry_year,
            'name' => $this->name,
            'bank' => $this->bank,
            'country_code' => $this->country_code,
            'authorization' => $this->authorization
        ];
    }
}
