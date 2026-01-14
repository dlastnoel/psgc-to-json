<?php

namespace App\Http\Resources;

use App\Http\Resources\ProvinceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'correspondence_code' => $this->correspondence_code,
            'geographic_level' => $this->geographic_level,
            'psgc_version_id' => $this->psgc_version_id,
            'provinces' => ProvinceResource::collection($this->whenLoaded('provinces')),
        ];
    }
}
