<?php

namespace App\Models;

use App\Http\Resources\TransactionResource;
use App\Services\Disputable\Disputable;
use App\Services\Disputable\DisputableTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use Walletable\Models\Transaction as Model;

class Transaction extends Model implements Disputable
{
    use DisputableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array'
    ];

    /**
     * @inheritdoc
     */
    public function getResource(): JsonResource
    {
        return new TransactionResource($this);
    }
}
