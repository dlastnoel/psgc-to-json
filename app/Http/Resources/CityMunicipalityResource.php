<?php

namespace App\Http\Resources;

use App\Http\Resources\BarangayResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityMunicipalityResource extends JsonResource
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
            'region_code' => $this->region_code,
            'province_id' => $this->province_id,
            'province_code' => $this->province_code,
            'is_capital' => (bool) $this->is_capital,
            'psgc_version_id' => $this->psgc_version_id,
            'barangays' => BarangayResource::collection($this->whenLoaded('barangays')),
        ];
    }
}
