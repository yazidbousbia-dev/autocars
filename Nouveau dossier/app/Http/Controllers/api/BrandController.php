<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    // PUBLIC: list all brands (for filter dropdowns)
    public function index()
    {
        return response()->json(Brand::orderBy('name')->get());
    }

    // ADMIN: create a new brand
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:brands,name',
            'logo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $brand = Brand::create($validator->validated());

        return response()->json(['message' => 'Brand created', 'brand' => $brand], 201);
    }

    // ADMIN: update a brand
    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:brands,name,'.$brand->id,
            'logo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $brand->update($validator->validated());

        return response()->json(['message' => 'Brand updated', 'brand' => $brand]);
    }

    // ADMIN: delete a brand
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Brand deleted']);
    }
}
