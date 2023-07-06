<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
        $provider = [];
        $providerType = "Individual";

        if (!$this->user()->get()[0]->business){
            $provider['name'] = $this->user()->get()[0]->getNameAttribute();
            $provider['image'] = $this->user()->get()[0]->profile_img;
        }else{
            $provider['name'] = $this->user()->get()[0]->rep_name .' @ '. $this->user()->get()[0]->business_name;
            $provider['image'] = $this->user()->get()[0]->profile_img;
            $providerType = "Business";
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'coordinate' => $this->coordinate,
            'price' => $this->price,
            'description' => $this->description,
            'duration' => $this->duration,
            'average_rating' => $this->avg_rating() ?? 0.0,
            'provider' => $provider,
            'providerType' => $providerType,
            'attachments' => $this->attachments()->get(['type', 'file', 'mime_type', 'extention', 'size']),
            'status' => $this->status,
        ];
    }
}
