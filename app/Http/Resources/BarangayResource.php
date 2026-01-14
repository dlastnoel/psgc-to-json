<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangayResource extends JsonResource
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
            'region_id' => $this->region_id,
            'province_id' => $this->province_id,
            'city_municipality_id' => $this->city_municipality_id,
            'psgc_version_id' => $this->psgc_version_id,
        ];
    }
}
