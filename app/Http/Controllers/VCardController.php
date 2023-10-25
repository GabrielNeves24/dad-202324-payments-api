<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VCard;

class VCardController extends Controller
{
    public function index()
    {
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function show($phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        return response()->json(['vcard' => $vcard], 200);
    }
}
