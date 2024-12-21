<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosnetPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function testSuccessfulPayment()
    {
        // Crear una tarjeta con suficiente límite
        $card = Card::create([
            'card_type' => 'Visa',
            'bank_name' => 'Banco Nación',
            'card_number' => '12345678',
            'limit' => 10000.00,
            'dni' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        // Realizar un pago exitoso
        $response = $this->postJson('/api/do-payment', [
            'card_number' => '12345678',
            'amount' => 2000.00,
            'installments' => 3,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'ticket' => [
                         'name',
                         'total_amount',
                         'installment_amount',
                     ],
                 ]);

        $this->assertDatabaseHas('payments', [
            'card_id' => $card->id,
            'amount' => 2000.00,
        ]);
    }

    public function testPaymentFailsDueToInsufficientLimit()
    {
        // Crear una tarjeta con límite insuficiente
        $card = Card::create([
            'card_type' => 'Visa',
            'bank_name' => 'Banco Nación',
            'card_number' => '12345678',
            'limit' => 1000.00,
            'dni' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        // Intentar realizar un pago con un monto superior al límite
        $response = $this->postJson('/api/do-payment', [
            'card_number' => '12345678',
            'amount' => 2000.00,
            'installments' => 3,
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'error' => 'Insufficient limit on card',
                 ]);

        $this->assertDatabaseMissing('payments', [
            'card_id' => $card->id,
            'amount' => 2000.00,
        ]);
    }
}
