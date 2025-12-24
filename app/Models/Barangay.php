<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barangay extends Model
{
    protected $fillable = [
        'region_id',
        'province_id',
        'city_municipality_id',
        'code',
        'name',
        'correspondence_code',
        'geographic_level',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function cityMunicipality(): BelongsTo
    {
        return $this->belongsTo(CityMunicipality::class);
    }
}
