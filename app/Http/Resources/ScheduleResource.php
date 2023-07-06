<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
        $receiver = [];
        $receiverType = "Individual";

        if (!$this->user()->get()[0]->business){
            $receiver['name'] = $this->user()->get()[0]->getNameAttribute();
            $receiver['image'] = $this->user()->get()[0]->profile_img;
        }else{
            $receiver['name'] = $this->user()->get()[0]->rep_name .' @ '. $this->user()->get()[0]->business_name;
            $receiver['image'] = $this->user()->get()[0]->profile_img;
            $receiverType = "Business";
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'service_id' => $this->service_id,
            'dueDate' => $this->dueDate,
            'times' => $this->times,
            'location' => $this->location,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'receiver' => $receiver,
            'receiverType' => $receiverType,
            'attachments' => $this->attachments()->get(['type', 'file', 'mime_type', 'extention', 'size']),
            'status' => $this->status,
        ];
    }
}
