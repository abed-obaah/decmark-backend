<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Exception;
use Walletable\Money\Money;

class Refund
{
    use HasStatus;

    /**
     * Success Status
     *
     * @var string
     */
    public const SUCCESS = 'SUCCESS';

    /**
     * Failed status
     *
     * @var string
     */
    public const FAILED = 'FAILED';

    /**
     * Full Refund
     *
     * @var string
     */
    public const FULL = 'FULL';

    /**
     * Partial Refund
     *
     * @var string
     */
    public const PARTIAL = 'PARTIAL';

    /**
     * Refund id
     *
     * @var string
     */
    protected $id;

    /**
     * Transaction reference
     *
     * @var string
     */
    protected $reference;

    /**
     * Refund reason
     *
     * @var string
     */
    protected $reason;

    /**
     * Refund type
     *
     * @var string
     */
    protected $type;

    /**
     * Amount to refund
     *
     * @var Money
     */
    protected $amount;

    /**
     * Transaction label
     *
     * @var LabelInterface
     */
    protected $label;

    /**
     * Gateway driver
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Refund transaction
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * create new instance
     *
     * @param DriverInterface $driver
     * @param string $id
     * @param string $reference
     * @param string $status
     * @param string $type
     * @param Money $amount
     * @param LabelInterface $label
     * @param Transaction $transaction
     * @param string $reason = null
     */
    public function __construct(
        DriverInterface $driver,
        string $id,
        string $reference,
        string $status,
        string $type,
        Money $amount,
        LabelInterface $label,
        ?Transaction $transaction,
        string $reason = null
    ) {
        $this->validateStatus($status);
        $this->validateType($type);

        $this->driver = $driver;
        $this->id = $id;
        $this->reference = $reference;
        $this->label = $label;
        $this->transaction = $transaction;
        $this->status = $status;
        $this->type = $type;
        $this->amount = $amount;
        $this->reason = $reason;
    }

    /**
     * Get refund id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get refund reference
     *
     * @return string
     */
    public function reference(): string
    {
        return $this->reference;
    }

    /**
     * Get refund reason
     *
     * @return string
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Get refund type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Check the refund type
     *
     * @return bool
     */
    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Get refund amount
     *
     * @return Money
     */
    public function amount(): Money
    {
        return $this->amount;
    }

    /**
     * Refund transaction
     *
     * @return Transaction
     */
    public function transaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Refund label
     *
     * @return LabelInterface
     */
    public function label(): LabelInterface
    {
        return $this->label;
    }

    /**
     * Refund driver
     *
     * @return DriverInterface
     */
    public function driver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Validate status
     *
     * @return void
     */
    protected function validateStatus(string $status)
    {
        if (!in_array($status, $statuses = [self::SUCCESS, self::FAILED])) {
            throw new Exception(sprintf('Status must be in [%s]', implode(',', $statuses)));
        }
    }

    /**
     * Validate types
     *
     * @return void
     */
    protected function validateType(string $type)
    {
        if (!in_array($type, $types = [self::FULL, self::PARTIAL])) {
            throw new Exception(sprintf('Status must be in [%s]', implode(',', $types)));
        }
    }
}
