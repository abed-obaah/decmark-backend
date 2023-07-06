<?php

namespace App\Http\Resources;

use App\Services\Utils;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->business) {
            $name = $this->rep_name . ' @ ' . $this->business_name;
            $type = 'Business';
        } else {
            $name = $this->name;
            $type = 'Individual';
        }
        return [
            'name' => $name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'profile_img' => $this->profile_img,
            'tag' => $this->when(!is_null($this->tag), $this->tag),
            'type' => $type,
            'email' => $this->email,
            'status' => $this->when(!is_null($this->status), $this->status),
            'phone' => $this->phone,
            'gender' => $this->when(!is_null($this->gender), $this->gender),
            'occupation' => $this->when(!is_null($this->occupation), $this->occupation),
            'state' => $this->when(!is_null($this->state), $this->state),
            'city' => $this->when(!is_null($this->city), $this->city),
            'address' => $this->when(!is_null($this->address), $this->address),
            'home_description' => $this->when(!is_null($this->home_description), $this->home_description),
            'created_at' => $this->created_at,
            'wallet' => $this->when(
                !is_null($this->wallet),
                new WalletResource($this->wallet)
            ),
        ];
    }
}
