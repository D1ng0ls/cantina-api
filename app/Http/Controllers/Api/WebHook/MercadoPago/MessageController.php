<?php

namespace App\Http\Controllers\Api\WebHook\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function handle(Request $request)
    {
        $signatureHeader = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (!$signatureHeader) {
            return response()->json(['error' => 'Signature header missing'], 400);
        }

        $ts = null;
        $hash = null;
        $parts = explode(',', $signatureHeader);
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part, 2);
            if ($key === 'ts') {
                $ts = $value;
            } elseif ($key === 'v1') {
                $hash = $value;
            }
        }

        if (!$ts || !$hash) {
            return response()->json(['error' => 'Invalid signature format'], 400);
        }
        
        $secret = config('services.mercadopago.webhook_secret');

        if (!$secret) {
            Log::error('MERCADOPAGO_WEBHOOK_SECRET não está configurada!');
            return response()->json(['error' => 'Server configuration error'], 500);
        }

        $dataId = $request->input('data.id');

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";

        $calculatedHash = hash_hmac('sha256', $manifest, $secret);

        if (!hash_equals($calculatedHash, $hash)) {
            Log::warning('Falha na validação do Webhook do MP', [
                'manifest_esperado' => $manifest,
                'hash_calculado' => $calculatedHash,
                'hash_recebido' => $hash,
                'request_id' => $requestId,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        Log::info('Webhook MP recebido e validado com sucesso!', $payload);

        return response()->json(['success' => true], 200);
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
