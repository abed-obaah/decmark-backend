<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourierResource extends JsonResource
{
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
            'title' => $this->title,
            'description' => $this->description,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'artisan' => new UserResource($this->artisan),
            'user' => $this->when(!is_null($this->user), new UserResource($this->user)),
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
