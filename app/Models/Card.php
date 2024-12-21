<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    // Atributos rellenables
    protected $fillable = [
        'card_type',
        'bank_name',
        'card_number',
        'limit',
        'dni',
        'first_name',
        'last_name',
    ];

    // Relación con pagos
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
