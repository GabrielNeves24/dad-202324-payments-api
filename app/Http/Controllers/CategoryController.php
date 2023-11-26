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
                return response()->json(['message' => 'Category created', 'data' => $category], 201);
            }catch(\Exception $e) {
                return response()->json(['message' => 'Error creating category: ' . $e->getMessage()], 409);
            }

        }
    }

    public function updateCategoryById(Request $request){
        // Validate the incoming request data
        $validatedData = $request->validate([
            'id' => 'required|integer', // Add any validation rules you need
            'name' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'vcard' => 'sometimes|required|string',
        ]);
        $phone_number= $request->vcard;
        $id = $request->id;
        // Check if the VCard with the provided phone number exists
        $category = Category::where('vcard', $phone_number)->where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => "Categoria $id não encontrada"], 404);
        }

        // Update only if fields are present and not empty
        if ($request->filled('name')) {
            $category->name = $validatedData['name'];
        }
        if ($request->filled('type')) {
            $category->type = $validatedData['type'];
        }
        if ($request->filled('vcard')) {
            $category->vcard = $validatedData['vcard'];
        }

        $category->save();

        return response()->json(['message' => "Categoria $id atualizada com sucesso", 'data' => $category], 200);
    }

    public function getCategoriesbyphoneNumber($phone_number)
    {
        //$vcard = VCard::findOrFail($phone_number);
        $categories = Category::where('vcard', $phone_number)->get();
        //caso vazia sem categorias
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }

    //methods store, 
    //update, and 
    //destroy are not implemented in this version of the API
}

