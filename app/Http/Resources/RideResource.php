<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
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
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'current_location_coordinate' => $this->current_coordinate,
            'destination_coordinate' => $this->destination_coordinate,
            'ride_type' => $this->ride_type,
            'passengers_count' => $this->passengers_count,
            'scheduled_at' => $this->scheduled_at,
            'created_at' => $this->created_at,
        ];
    }

    // public function with($request)
    // {
    //     return ['status' => 'success'];
    // }
}

