<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a PSGC publication version with its imported data.
 */
class PsgcVersion extends Model
{
    protected $fillable = [
        'quarter',
        'year',
        'publication_date',
        'download_url',
        'filename',
        'is_current',
        'regions_count',
        'provinces_count',
        'cities_municipalities_count',
        'barangays_count',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }

    public function citiesMunicipalities(): HasMany
    {
        return $this->hasMany(CityMunicipality::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }

    /**
     * Scope to get the current PSGC version.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Set this version as current and mark all others as not current.
     */
    public function setCurrent(): void
    {
        static::query()->update(['is_current' => false]);
        $this->update(['is_current' => true]);
    }

    /**
     * Get the current PSGC version instance.
     */
    public static function getCurrentVersion(): ?self
    {
        return static::current()->first();
    }
}
