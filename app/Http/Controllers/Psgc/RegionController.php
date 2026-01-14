<?php

namespace App\Http\Controllers\Psgc;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Region::query();

        // Filter by version if provided
        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            // Default to current version
            $query->current();
        }

        $regions = $query->with('provinces')->get();

        return RegionResource::collection($regions)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Region $region): JsonResponse
    {
        $query = Region::where('id', $region->id);

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        $region = $query->with('provinces.citiesMunicipalities.barangays')->firstOrFail();

        return RegionResource::make($region)->response();
    }
}
