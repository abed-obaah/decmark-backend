<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'by',
        'for',
        'type',
        'review',
        'score'
    ];

    /**
     * User that creates the review
     *
     * @return BelongsTo
     */
    public function by(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avg()
    {
        return $this->by()->first()->avg_rating();
    }
}
