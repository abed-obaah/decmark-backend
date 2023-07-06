<?php

namespace App\Models;

use App\Models\Schedule;
use App\Traits\ModelTrait;
use App\Enums\ServiceStatusEnum;
use App\Http\Resources\ServiceResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;
    use ModelTrait;
    use SpatialTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'coordinate',
        'title',
        'type',
        'price',
        'description',
        'duration',
        'status'
    ];

    protected $attributes = [
        'status' => ServiceStatusEnum::PENDING,
    ];

    protected $spatialFields = [
        'coordinate'
    ];

    public function getCoordinateAttribute()
    {
        return [
            $this->getRawOriginal('coordinate')->getLng(),
            $this->getRawOriginal('coordinate')->getLat()
        ];
    }

    /**
     * User that creates the service
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Service attachments
     *
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'owner');
    }

    public function parentResource(): ServiceResource
    {
        return new ServiceResource($this);
    }

    /**
     * Ratings
     *
     * @return hasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'for');
    }

    /**
     * User Schedules
     *
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Average Rating
     *
     * @return hasMany
     */
    public function avg_rating()
    {
        return Rating::where([
            ['for', $this->getKey()],
            ['type', 'SERVICE'],
            ])->avg('score');
    }
}
