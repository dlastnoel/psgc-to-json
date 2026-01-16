<?php

namespace App\Http\Resources;

use App\Http\Resources\CityMunicipalityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
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
            'province_code' => $this->province_code,
            'psgc_version_id' => $this->psgc_version_id,
            'cities_municipalities' => CityMunicipalityResource::collection($this->whenLoaded('citiesMunicipalities')),
        ];
    }
}
