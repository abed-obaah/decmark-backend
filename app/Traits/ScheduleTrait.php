<?php

namespace App\Traits;

use App\Models\Service;
use Illuminate\Support\Str;

trait ScheduleTrait
{
    /**
     * Boot function from laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->getKey()) && $model->usesUuid()) {
                $model->{$model->getKeyName()} = Str::Uuid();
            }

            $model->price = Service::where('id', $model->service_id)->first()->price ?? 0;
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Determines if a model uses uuid as its primary.
     *
     * @return bool
     */
    public function usesUuid(): bool
    {
        return true;
    }

    /**
     * Get model table name.
     */
    public static function table(): string
    {
        return \app(static::class)->getTable();
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
}
