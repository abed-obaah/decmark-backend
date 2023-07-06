<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankCardResource extends JsonResource
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
            'label' => $this->when(!is_null($this->label), $this->label),
            'name' => $this->when(!is_null($this->name), $this->name),
            'number' => $this->when(!is_null($this->number), $this->number),
            'expiry_month' => $this->when(!is_null($this->expiry_month), $this->expiry_month),
            'expiry_year' => $this->when(!is_null($this->expiry_year), $this->expiry_year),
            'brand' => $this->when(!is_null($this->brand), $this->brand)
        ];
    }
}
