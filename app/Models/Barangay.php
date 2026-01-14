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
        'psgc_version_id',
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

    public function psgcVersion(): BelongsTo
    {
        return $this->belongsTo(PsgcVersion::class);
    }

    /**
     * Scope to get records from current PSGC version.
     */
    public function scopeCurrent($query)
    {
        return $query->where('psgc_version_id', PsgcVersion::getCurrentVersion()?->id);
    }

    /**
     * Scope to get records from specific PSGC version.
     */
    public function scopeVersion($query, int $versionId)
    {
        return $query->where('psgc_version_id', $versionId);
    }
}
