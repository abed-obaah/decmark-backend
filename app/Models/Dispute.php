<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Dispute extends Model
{
    use HasFactory;
    use ModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'flags',
        'title',
        'note'
    ];

    /**
     * Get the disputable relationship
     */
    public function disputable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the disputer relationship
     */
    public function disputer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the resolver relationship
     */
    public function resolver(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark dispute as resolved
     */
    public function markAsResolved()
    {
        return $this->forceFill([
            'resolved_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Scope for unresolved disputes
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope for resolved disputes
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved_at', '<>', null);
    }
}
