<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index($vcard)
    {
        // Get categories associated with a specific vCard
        $categories = Category::where('vcard', $vcard)->get();
        return response()->json(['categories' => $categories], 200);
    }

    public function show($vcard, $id)
    {
        // Get a specific category associated with a specific vCard
        $category = Category::where('vcard', $vcard)->findOrFail($id);
        return response()->json(['category' => $category], 200);
    }

    //methods store, 
    //update, and 
    //destroy 

}
