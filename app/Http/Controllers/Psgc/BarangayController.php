<?php

namespace App\Http\Controllers\Psgc;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangayResource;
use App\Models\Barangay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Barangay::query();

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        if ($request->has('city_municipality_id')) {
            $query->where('city_municipality_id', $request->integer('city_municipality_id'));
        }

        if ($request->has('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        if ($request->has('region_id')) {
            $query->where('region_id', $request->integer('region_id'));
        }

        $barangays = $query->get();

        return BarangayResource::collection($barangays)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Barangay $barangay): JsonResponse
    {
        $query = Barangay::where('id', $barangay->id);

        if ($request->has('version')) {
            $query->version($request->integer('version'));
        } else {
            $query->current();
        }

        $barangay = $query->with('cityMunicipality.province.region')->firstOrFail();

        return BarangayResource::make($barangay)->response();
    }
}
