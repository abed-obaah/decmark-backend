<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'data'
    ];

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getData()
    {
        return json_decode($this->data);
    }

}
