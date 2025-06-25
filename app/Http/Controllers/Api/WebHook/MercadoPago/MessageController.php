<?php

namespace App\Http\Controllers\Api\WebHook\MercadoPago;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\MercadoPagoService;
use App\Http\Controllers\Controller;
use App\Models\Order;

class MessageController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $service) {
        $this->mercadoPagoService = $service;
    }

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

        Log::info('Webhook MP recebido e validado com sucesso!', $request->all());

        // --- A MÁGICA ACONTECE AQUI! ---
    
        // NOVO! Checa se é um evento do tipo 'payment'.
        if ($request->input('type') !== 'payment') {
            // Se não for de pagamento, a gente só ignora e encerra.
            return response()->json(['status' => 'webhook nao relacionado a pagamento'], 200);
        }
    
        // NOVO! Pega o ID do pagamento. Você já tinha ele na variável $dataId.
        $paymentId = $request->input('data.id');
    
        // NOVO! Chama nosso serviço para buscar o status atual do pagamento.
        $paymentInfo = $this->mercadoPagoService->checkPaymentStatus($paymentId);
    
        // NOVO! Agora a gente decide o que fazer com base na resposta.
        if ($paymentInfo && $paymentInfo['status'] === 'approved') {
            Log::info("Pagamento {$paymentId} APROVADO! Iniciando atualização no banco.");
        } else if ($paymentInfo) {
            // Se o status for outro (rejected, pending, etc), a gente só registra.
            Log::warning("Pagamento {$paymentId} com status: {$paymentInfo['status']}. Nenhuma ação necessária.");
        } else {
            // Se o paymentInfo veio nulo, nosso serviço já logou o erro.
            Log::error("Não foi possível obter informações para o pagamento {$paymentId}.");
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
