<?php

namespace App\Models;

use App\Models\Schedule;
use App\Traits\ModelTrait;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Walletable\Contracts\Walletable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail, Walletable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use ModelTrait;
    use SpatialTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'profile_img',
        'phone',
        'tag',
        'email',
        'password',
        'pin',
        'gender',
        'state',
        'city',
        'address',
        'home_description',
        'rider_coordinate',
        'artisan_coordinate',
        'coordinate',
        'referrer_id',
        'business',
        'business_name',
        'rep_name',
        'rep_position',
        'fb_id',
        'fcm_tokens',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime'
    ];

    protected $spatialFields = [
        'rider_coordinate',
        'artisan_coordinate',
        'coordinate',
    ];

    public function getNameAttribute()
    {
        return $this->business ? $this->first_name . ' @ ' . $this->last_name : $this->first_name . ' ' . $this->last_name;
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    public function scopeIsRider(Builder $query)
    {
        $query->where('rider', true);
    }

    public function scopeArtisans(Builder $query)
    {
        $query->where('artisan', true);
    }

    public function scopeArtisansCloseTo(Builder $query, $longitude, $latitude, $meters)
    {
        if ($query->getQuery()->connection instanceof SQLiteConnection) {
            return;
        }

        $query->whereRaw(
            "ST_Distance_Sphere( `" . self::table() . "`.`artisan_coordinate`, POINT(?, ?)) < ?",
            [
                $longitude,
                $latitude,
                $meters
            ]
        );
    }

    public function scopeRiders(Builder $query)
    {
        $query->where('rider', true);
    }

    public function scopeRidersCloseTo(Builder $query, $longitude, $latitude, $meters)
    {
        if ($query->getQuery()->connection instanceof SQLiteConnection) {
            return;
        }

        $query->whereRaw(
            "ST_Distance_Sphere( `" . self::table() . "`.`rider_coordinate`, POINT(?, ?)) < ?",
            [
                $longitude,
                $latitude,
                $meters
            ]
        );
    }

    /**
     * Get user virtual card
     */
    public function activationCodes(): MorphMany
    {
        return $this->morphMany(ActivationCode::class, 'owner');
    }

    /**
     * Get user virtual card
     */
    public function forActivationCodes(): MorphMany
    {
        return $this->morphMany(ActivationCode::class, 'for');
    }

    /**
     * Mark the given user's phone as verified.
     *
     * @return bool
     */
    public function markPhoneAsVerified()
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the name of wallet owner
     *
     * @return string
     */
    public function getOwnerName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the email of wallet owner
     *
     * @return string
     */
    public function getOwnerEmail()
    {
        return $this->email;
    }

    /**
     * Get the ID of owner
     *
     * @return string
     */
    public function getOwnerID()
    {
        return $this->getKey();
    }

    /**
     * Get the ID of owner
     *
     * @return string
     */
    public function getOwnerImage()
    {
        return $this->profile_img;
    }

    /**
     * Get the morph name of owner
     *
     * @return string
     */
    public function getOwnerMorphName()
    {
        return $this->getMorphClass();
    }

    /**
     * User wallets relationship
     *
     * @return MorphMany
     */
    public function wallets(): MorphMany
    {
        return $this->MorphMany(Wallet::class, 'walletable');
    }

    public function transactions(): Builder
    {
        $wallets = $this->wallets()
            ->get('id')->pluck('id')->toArray();
        return Transaction::whereIn('wallet_id', $wallets)->latest('created_at');
    }

    /**
     * Bank Cards relationship
     *
     * @return MorphMany
     */
    public function bankCards(): MorphMany
    {
        return $this->morphMany(BankCard::class, 'owner');
    }

    /**
     * User services
     *
     * @return HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * User schedules
     *
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * User services
     *
     * @return HasMany
     */
    public function userRides(): HasMany
    {
        return $this->hasMany(RideHailing::class);
    }

    /**
     * User attachments
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * User logs
     *
     * @return HasOne
     */
    public function logs(): HasOne
    {
        return $this->hasOne(UserLog::class);
    }

    /**
     * Add Activity
     */
    public function add_log($text, $data)
    {
        $input = [
            'message' => $text,
            'x' => $data,
            'time' => now(),
        ];
        if (count($this->logs()->get()) < 1){
            $this->logs()->create([
               'data' => json_encode([$input]),
            ]);
        }else{
            $log = $this->logs()->first();
            $data = $log->getData();
            array_unshift($data, $input);

            $log->update([
                'data' => json_encode($data),
            ]);
        }
    }

    /**
     * Get Referrees
     *
     * @return HasMany
    */

    public function referees() : HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * Get Referrer
     *
     * @return HasOne
    */

    public function referral() : HasOne
    {
        return $this->hasOne(Referral::class, 'referree_id');
    }

    /**
     * Artisan courier
     *
     * @return hasMany
     */
    public function artisanCouriers(): HasMany
    {
        return $this->hasMany(Courier::class, 'artisan_id');
    }

    /**
     * Artisan courier
     *
     * @return hasMany
     */
    public function userCouriers(): HasMany
    {
        return $this->hasMany(Courier::class);
    }

    /**
     * Ratings
     *
     * @return hasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'by');
    }
    
}
