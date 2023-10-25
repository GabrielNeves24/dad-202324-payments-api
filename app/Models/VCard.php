<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class VCard extends Model
{
    use HasFactory;
    //usar a tabela vcards
    protected $table = 'vcards';
    //id da tabela é phone_number
    protected $primaryKey = 'phone_number';

    public function categories()
    {
        return $this->hasMany(Category::class, 'vcard', 'phone_number');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'vcard', 'phone_number');
    }

    

    
}
