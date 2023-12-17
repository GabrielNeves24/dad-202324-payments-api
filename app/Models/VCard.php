<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;


class VCard extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $table = 'vcards';
    protected $primaryKey = 'phone_number';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'name',
        'email',
        'photo_url',
        'password',
        'confirmation_code',
        'blocked',
        'balance',
        'max_debit',
    ];


    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function getAuthPassword()
    {
        return $this->password;
    }

}
