<?php

namespace App\Http\Controllers\Psgc;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Province::query();

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        if ($request->has('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        $provinces = $query->with('citiesMunicipalities')->get();

        return ProvinceResource::collection($provinces)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Province $province): JsonResponse
    {
        $query = Province::where('id', $province->id);

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        $province = $query->with('region', 'citiesMunicipalities.barangays')->firstOrFail();

        return ProvinceResource::make($province)->response();
    }
}
