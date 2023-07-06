<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\SQLiteConnection;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class Courier extends Model
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
        'title',
        'price',
        'origin',
        'destination',
        'artisan_accept',
        'user_accept',
        'description',
        'status',
    ];

    protected $spatialFields = [
        'origin',
        'destination',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'artisan_accept' => 'bool',
        'user_accept' => 'bool',
    ];

    public function getOriginAttribute()
    {
        return [
            $this->getRawOriginal('origin')->getLng(),
            $this->getRawOriginal('destination')->getLat()
        ];
    }

    public function getDestinationAttribute()
    {
        return [
            $this->getRawOriginal('origin')->getLng(),
            $this->getRawOriginal('destination')->getLat()
        ];
    }

    public function scopeDepaturePoint(Builder $query, $longitude, $latitude)
    {
        if ($query->getQuery()->connection instanceof SQLiteConnection) {
            return;
        }

        $query->equals('origin', new Point($longitude, $latitude));
    }
    

    public function scopeArrivalPoint(Builder $query, $longitude, $latitude)
    {
        if ($query->getQuery()->connection instanceof SQLiteConnection) {
            return;
        }

        $query->equals('destination', new Point($longitude, $latitude));
    }
    
    /**
     * User that creates the service
     *
     * @return BelongsTo
     */
    public function artisan(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
