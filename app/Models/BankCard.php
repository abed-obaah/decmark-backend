<?php

namespace App\Models;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\HasGateway;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankCard extends Model implements HasGateway
{
    use HasFactory;
    use ModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label',
        'reference',
        'token',
        'name',
        'number',
        'expiry_month',
        'expiry_year',
        'brand',
        'driver',
        'paid_at',
        'refunded_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'token' => 'encrypted',
    ];

    /**
     * Owner relationship
     *
     * @return MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @inheritDoc
     */
    public function getGateway(): DriverInterface
    {
        return Gateway::driver($this->driver);
    }
}
