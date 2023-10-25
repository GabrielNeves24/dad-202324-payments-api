<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    protected $fillable = [
        'vcard',
        'date',
        'datetime',
        'type',
        'value',
        'old_balance',
        'new_balance',
        'payment_type',
        'payment_reference',
        'pair_transaction',
        'pair_vcard',
        'category_id',
        'description',
        'custom_options',
        'custom_data',
    ];

    /**
     * Get the vCard associated with the transaction.
     */
    public function vcard()
    {
        return $this->belongsTo(VCard::class, 'vcard', 'phone_number');
    }

    /**
     * Get the category associated with the transaction.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the pair transaction associated with the transaction.
     */
    public function pairTransaction()
    {
        return $this->belongsTo(Transaction::class, 'pair_transaction');
    }

    /**
     * Get the paired vCard associated with the transaction.
     */
    public function pairVCard()
    {
        return $this->belongsTo(VCard::class, 'pair_vcard', 'phone_number');
    }
}
