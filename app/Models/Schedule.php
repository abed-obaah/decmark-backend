<?php

namespace App\Models;

use App\Traits\ScheduleTrait;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;
    use ScheduleTrait;
    use SpatialTrait;

    protected $fillable = [
        'user_id',
        'service_id',
        'dueDate',
        'price',
        'times',
        'location',
        'description',
        'status'
    ];

    protected $spatialFields = [
        'location'
    ];

    public function getCoordinateAttribute()
    {
        return [
            $this->getRawOriginal('location')->getLng(),
            $this->getRawOriginal('location')->getLat()
        ];
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

    /**
     * User that creates the schedule
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
