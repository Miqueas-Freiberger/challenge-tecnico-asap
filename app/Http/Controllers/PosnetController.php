<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PosnetController extends Controller
{
    // Registrar tarjeta
    public function registerCard(Request $request)
    {
        try {
            $request->validate([
                'card_type' => 'required|in:Visa,AMEX',
                'bank_name' => 'required|string',
                'card_number' => 'required|digits:8|unique:cards',
                'limit' => 'required|numeric|min:0',
                'dni' => 'required|string',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
            ]);

            $card = Card::create($request->all());

            return response()->json(['message' => 'Card registered successfully', 'card' => $card], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred', 'details' => $e->getMessage()], 500);
        }
    }

    // Procesar pago
    public function doPayment(Request $request)
    {
        try {
            $request->validate([
                'card_number' => 'required|exists:cards,card_number',
                'amount' => 'required|numeric|min:0.01',
                'installments' => 'required|integer|min:1|max:6',
            ]);

            $card = Card::where('card_number', $request->card_number)->firstOrFail();

            $recargo = $request->installments > 1 ? ($request->installments - 1) * 0.03 : 0;
            $total = $request->amount * (1 + $recargo);

            if ($total > $card->limit) {
                return response()->json(['error' => 'Insufficient limit on card'], 400);
            }

            // Actualizar límite de la tarjeta
            $card->limit -= $total;
            $card->save();

            // Crear el registro de pago
            $payment = Payment::create([
                'card_id' => $card->id,
                'amount' => $request->amount,
                'installments' => $request->installments,
                'total_amount' => $total,
                'installment_amount' => $total / $request->installments,
            ]);

            return response()->json([
                'ticket' => [
                    'name' => $card->first_name . ' ' . $card->last_name,
                    'total_amount' => $total,
                    'installment_amount' => $payment->installment_amount,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Card not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred', 'details' => $e->getMessage()], 500);
        }
    }
}
