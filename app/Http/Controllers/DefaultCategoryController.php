<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DefaultCategory;

class DefaultCategoryController extends Controller
{
    public function categoriesDefaultAll()
    {
        $defaultCategories = DefaultCategory::all();
        return response()->json(['data' => $defaultCategories], 200);
    }

    public function show(Request $request)
    {
        $defaultCategory = DefaultCategory::findOrFail($request->id);
        return response()->json(['data' => $defaultCategory], 200);
    }

    public function createCategoriesDefault(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'type' => 'required|max:1',
        ]);
        $defaultCategory = DefaultCategory::create($validatedData);
        return response()->json(['data' => $defaultCategory], 201);
    }

    public function updateCategoriesDefault(Request $request, $id)
    {
        $defaultCategory = DefaultCategory::findOrFail($id);
        $defaultCategory->update($request->all());
        return response()->json(['data' => $defaultCategory], 200);
    }

    public function deleteCategoriesDefault(Request $request, $id)
    {
        $defaultCategory = DefaultCategory::findOrFail($id);
        $defaultCategory->delete();
        return response()->json(['data' => $defaultCategory], 200);
    }

    public function categoriesDefaultByID(Request $request, $id)
    {
        $defaultCategory = DefaultCategory::findOrFail($id);
        return response()->json(['data' => $defaultCategory], 200);
    }
}

