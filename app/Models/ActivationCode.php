<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivationCode extends Model
{
    use HasFactory;
    use ModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'action',
        'expires_at'
    ];

    /**
     * Determines if the model uses uuid as it`s primary.
     *
     * @return bool
     */
    public function usesUuid(): bool
    {
        return false;
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hash activation tokens
     *
     * @param string $string
     * @return string
     */
    public static function hash(string $string)
    {
        return \hash('sha256', $string);
    }
}
