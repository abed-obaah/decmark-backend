<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'title' => $this->when(!is_null($this->title), $this->title),
            'image' => $this->when(!is_null($this->image), $this->image),
            'remarks' => $this->when(!is_null($this->remarks), $this->remarks),
            'amount' => $this->amount,
            'balance' => $this->balance,
            'action' => $this->getRawOriginal('action'),
            'session' => $this->session,
            'created_at' => $this->created_at,
        ];
    }
}
