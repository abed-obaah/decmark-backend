<?php

namespace App\Models;

use App\Enums\AdminStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Admin extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use ModelTrait;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];



    protected $attributes = [
        'status' => AdminStatusEnum::ACTIVE,
    ];

    public function activationCodes(): MorphMany
    {
        return $this->morphMany(ActivationCode::class, 'owner');
    }
}
