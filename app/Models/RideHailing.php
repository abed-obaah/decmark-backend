<?php

namespace App\Models;

use App\Http\Resources\RideResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\SQLiteConnection;

class RideHailing extends Model
{
    use HasFactory;
    use ModelTrait;
    use SpatialTrait;

    protected $fillable = ['rider_rating', 'rider_review','rider_id', 'ride_type', 'passengers_count', 'user_id', 'scheduled_at', 'current_coordinate', 'destination_coordinate'];

    protected $spatialFields = [
        'current_coordinate',
        'destination_coordinate',
    ];


    /**
     * User that creates the service
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function scopeRiders(Builder $query)
    {
        $query->where('rider', true);
    }

    public function scopeRidersCloseTo(Builder $query, $longitude, $latitude, $meters)
    {
        $query->whereRaw(
            "ST_Distance_Sphere( `" . self::table() . "`.`current_coordinate`, POINT(?, ?)) < ?",
            [
                $longitude,
                $latitude,
                $meters
            ]
        );
    }

    public function parentResource(): RideResource
    {
        return new RideResource($this);
    }
}
