<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DefaultCategory;

class DefaultCategoryController extends Controller
{
    public function index()
    {
        $defaultCategories = DefaultCategory::all();
        return response()->json(['default_categories' => $defaultCategories], 200);
    }

    public function show($id)
    {
        $defaultCategory = DefaultCategory::findOrFail($id);
        return response()->json(['default_category' => $defaultCategory], 200);
    }

    //methods store, 
    //update, and 
    //destroy 

}

