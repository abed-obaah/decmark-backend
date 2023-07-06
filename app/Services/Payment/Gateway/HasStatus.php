<?php

namespace App\Services\Payment\Gateway;

use Exception;
use ReflectionClass;

/**
 * Payment status trait
 */
trait HasStatus
{
    /**
     * Status
     *
     * @var string
     */
    protected $status;

    /**
     * Check status
     *
     * @return bool
     */
    public function check(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Get status string
     *
     * @return string
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * Validate status
     *
     * @return void
     */
    protected function validateStatus(string $status)
    {
        $statuses = \array_values((new ReflectionClass(self::class))->getConstants());

        if (!in_array($status, $statuses)) {
            throw new Exception(sprintf('Status must be in [%s]', implode(',', $statuses)));
        }
    }
}
