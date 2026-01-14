<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'region_id',
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

    public function citiesMunicipalities(): HasMany
    {
        return $this->hasMany(CityMunicipality::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }

    public function capitalCity(): HasMany
    {
        return $this->hasMany(CityMunicipality::class)->where('is_capital', true);
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
