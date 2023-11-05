<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    //table name categories
    protected $table = 'categories';

    //join Category com Vcard, vcard=phone_number
    public function vcard()
    {
        return $this->belongsTo(VCard::class, 'phone_number', 'id');
    }

    //connect no many category:id on table transacions
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id', 'id');
    }


}
