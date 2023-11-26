<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\VCard;
use App\Models\Transaction;

class CategoryController extends Controller
{
    public function index($vcard)
    {
        // Get categories associated with a specific vCard
        $categories = Category::where('vcard', $vcard)->get();
        return response()->json(['categories' => $categories], 200);
    }

    // public function getAllCategories(Request $request)
    // {
    //     $categories = Category::all();
    //     return response()->json(['data' => $categories], 200);
    // }

    public function show($vcard, $id)
    {
        // Get a specific category associated with a specific vCard
        $category = Category::where('vcard', $vcard)->findOrFail($id);
        return response()->json(['category' => $category], 200);
    }

    public function addCategoryByPhoneNumber(Request $request, $phone_number)
    {
        // Add a new category associated with a specific vCard
        $existeCategoria = Category::where('vcard', $phone_number)->where('name', $request->name)->first();
        if($existeCategoria != null) {
            return response()->json(['message' => 'Category already exists'], 409);
        }else{
            try{
                $validateData = $request->validate([
                    'name' => 'max:255',
                    'type' => 'max:255',
                    'vcard' => 'max:255',
                ]);
                $category = Category::create($validateData);
                return response()->json(['message' => 'Category created', 'category' => $category], 201);
            }catch(\Exception $e) {
                return response()->json(['message' => 'Error creating category: ' . $e->getMessage()], 409);
            }

        }
    }

    //methods store, 
    //update, and 
    //destroy are not implemented in this version of the API
}

