<?php

namespace App\Http\Controllers\Psgc;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityMunicipalityResource;
use App\Models\CityMunicipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityMunicipalityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CityMunicipality::query();

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        if ($request->has('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        if ($request->has('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        $citiesMunicipalities = $query->with('barangays')->get();

        return CityMunicipalityResource::collection($citiesMunicipalities)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, CityMunicipality $cityMunicipality): JsonResponse
    {
        $query = CityMunicipality::where('id', $cityMunicipality->id);

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        $cityMunicipality = $query->with('region', 'province', 'barangays')->firstOrFail();

        return CityMunicipalityResource::make($cityMunicipality)->response();
    }
}
