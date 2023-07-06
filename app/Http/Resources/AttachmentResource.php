<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends JsonResource
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
            'type' => $this->type,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'extention' => $this->extention,
            'size' => $this->size,
            'url' => Storage::url('attachments/' . $this->file),
            'user' => $this->whenLoaded('user', new CustomerResource($this->user)),
            'parent' => $this->whenLoaded('owner', $this->owner->parentResource()),
        ];
    }
}
