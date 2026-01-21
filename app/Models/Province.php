<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'region_id',
        'region_code',
        'province_code',
        'code',
        'name',
        'old_name',
        'correspondence_code',
        'geographic_level',
        'is_capital',
        'is_elevated_city',
        'is_virtual',
        'psgc_version_id',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_elevated_city' => 'boolean',
        'is_virtual' => 'boolean',
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

    /**
     * Scope to get elevated NCR cities.
     */
    public function scopeElevatedCities($query)
    {
        return $query->where('is_elevated_city', true);
    }

    /**
     * Scope to get actual provinces (not elevated cities).
     */
    public function scopeActualProvinces($query)
    {
        return $query->where('is_elevated_city', false);
    }

    /**
     * Scope to get capital provinces/cities.
     */
    public function scopeCapital($query)
    {
        return $query->where('is_capital', true);
    }

    /**
     * Scope to get virtual provinces (e.g., NCR virtual province).
     */
    public function scopeVirtual($query)
    {
        return $query->where('is_virtual', true);
    }

    /**
     * Scope to get real provinces only (not virtual).
     */
    public function scopeRealProvinces($query)
    {
        return $query->where('is_virtual', false);
    }
}
