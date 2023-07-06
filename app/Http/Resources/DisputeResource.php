<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
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
            'flags' => $this->flags,
            'title' => $this->title,
            'note' => $this->note,
            'type' => $this->disputable_type,
            'disputed' => $this->whenLoaded('disputable', function () {
                return $this->disputable->getResource();
            })
        ];
    }
}
