<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VCard;

class VCardController extends Controller
{
    public function index(){
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function getAllVCards()
    {
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function getVCardsbyphoneNumber($phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        return $vcard;
        //return response()->json(['vcard' => $vcard], 200);
    }

    public function getVCardImage($phone_number)
    {
        // Find the user's image file path
        $vcard = VCard::findOrFail($phone_number);
        $imagePath = public_path("storage/fotos/{$vcard->photo_url}");

        // Check if the image file exists
        if (file_exists($imagePath)) {
            // Return the image as a response
            return response()->file($imagePath, ['Content-Type' => 'image/jpg']);
        }

        // If the image doesn't exist, return a default image or an error response
        $defaultImagePath = public_path('storage/fotos/default.jpg');
        if (file_exists($defaultImagePath)) {
            return response()->file($defaultImagePath, ['Content-Type' => 'image/jpg']);
        } else {
            // If the default image doesn't exist, you can return a 404 response
            return response('Image not found', 404);
        }
    }


}
