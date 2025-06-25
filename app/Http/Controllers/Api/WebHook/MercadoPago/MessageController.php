<?php

namespace App\Http\Controllers\Api\WebHook\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;


class MessageController extends Controller
{
    public function handle(Request $request)
    {
        \Log::info($request->all());

        $signature = $request->header('x-signature');

        if (!$signature || !str_contains($signature, '=')) {
            return response()->json(['error' => 'Signature missing or invalid'], 400);
        }

        list($algo, $hash) = explode('=', $signature);
        $secret = config('services.mercadopago.webhook_secret');
        $payload = $request->getContent();
        $calculatedHash = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($calculatedHash, $hash)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = json_decode($payload, true);

        \Log::info('Webhook MP recebido', $data);

        return response()->json(['success' => true]);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',
        ]);

        $pedido = Order::find($request->id);

        if (!$pedido) {
            return response()->json([
                'error' => 'Pedido não encontrado.',
            ], 404);
        }

        $pedido->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'Status do pedido atualizado com sucesso',
        ]);
    }
}
