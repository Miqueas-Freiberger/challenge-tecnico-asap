<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Atributos rellenables
    protected $fillable = [
        'card_id',
        'amount',
        'installments',
        'total_amount',
        'installment_amount',
    ];

    // Relación con tarjeta
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
