<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
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
            'name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'artisan_type' => $this->artisan_type,
            'artisan_bio' => $this->artisan_bio,
            'address' => $this->address,
            'profile_img' => $this->profile_img,
            'joined' => $this->created_at->diffForHumans(),
            'services' => $this->services,
            'attachments' => AttachmentResource::collection($this->attachments),
        ];
    }
}
