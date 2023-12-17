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
        $categories = Category::where('vcard', $vcard)->get();
        return response()->json(['categories' => $categories], 200);
    }
    public function getAllCategories()
    {
        $categories = Category::all();
        return response()->json(['data' => $categories], 200);
    }


    public function show($vcard, $id)
    {
        $category = Category::where('vcard', $vcard)->findOrFail($id);
        return response()->json(['category' => $category], 200);
    }

    public function addCategoryByPhoneNumber(Request $request, $phone_number)
    {
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
        $validatedData = $request->validate([
            'id' => 'required|integer', 
            'name' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'vcard' => 'sometimes|required|string',
        ]);
        $phone_number= $request->vcard;
        $id = $request->id;
        $category = Category::where('vcard', $phone_number)->where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => "Categoria $id não encontrada"], 404);
        }
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
        $categories = Category::where('vcard', $phone_number)->get();
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }
}

