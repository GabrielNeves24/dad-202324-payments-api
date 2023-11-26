<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultCategory extends Model
{
    use HasFactory;
    // no timestamps
    public $timestamps = false;

    //table name categories
    protected $table = 'default_categories';

    protected $fillable = ['name', 'type'];
}
