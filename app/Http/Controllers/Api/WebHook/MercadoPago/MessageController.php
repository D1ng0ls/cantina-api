<?php

namespace App\Http\Controllers\Api\WebHook\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Services\MercadoPagoService;

class MessageController extends Controller
{

    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $service)
    {
        $this->mercadoPagoService = $service;
    }

    public function handle(Request $request)
    {
        // ===================================================================
        // ETAPA 1: VALIDAÇÃO DA ASSINATURA (SEU CÓDIGO, JÁ ESTÁ PERFEITO)
        // ===================================================================

        $signatureHeader = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (!$signatureHeader) {
            return response()->json(['error' => 'Signature header missing'], 400);
        }

        $ts = null;
        $hash = null;
        $parts = explode(',', $signatureHeader);
        foreach ($parts as $part) {
            // Usar array destructuring é um pouco mais limpo aqui
            [$key, $value] = explode('=', $part, 2);
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
            Log::error('MERCADOPAGO_WEBHOOK_SECRET não está configurada no .env!');
            return response()->json(['errors' => 'Server configuration error'], 500);
        }

        // Se o data.id não vier, a gente já para por aqui.
        $dataId = $request->input('data.id');
        if (!$dataId) {
            return response()->json(['error' => 'data.id is missing'], 400);
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $calculatedHash = hash_hmac('sha256', $manifest, $secret);

        if (!hash_equals($calculatedHash, $hash)) {
            \Log::warning('Falha na validação do Webhook do MP - Assinatura Inválida', [
                'request_id' => $requestId,
                'hash_recebido' => $hash,
                'hash_calculado' => $calculatedHash,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        \Log::info("Webhook do MP [{$requestId}] validado com sucesso. Processando payload.", $request->all());

        // Apenas processamos webhooks do tipo 'payment'.
        if ($request->input('type') !== 'payment') {
            \Log::info("Webhook [{$requestId}] ignorado: tipo '{$request->input('type')}' não é 'payment'.");
            return response()->json(['status' => 'webhook ignored, not a payment'], 200);
        }

        // Usa o serviço para consultar o status real do pagamento na API do MP.
        // O $dataId aqui é o ID do pagamento.
        $paymentInfo = $this->mercadoPagoService->checkPaymentStatus($dataId);

        // Se a consulta na API falhou, o serviço retornará null.
        if (!$paymentInfo) {
            \Log::error("Falha ao obter dados do pagamento [{$dataId}] na API do Mercado Pago.", [
                'request_id' => $requestId
            ]);
            // Mesmo com erro, respondemos 200 para o MP não ficar repetindo a notificação.
            // O erro já foi logado, vamos tratar internamente.
            return response()->json(['status' => 'failed to fetch payment info'], 200);
        }

        // O ponto principal: checar se o status é 'approved'.
        if (isset($paymentInfo['status']) && $paymentInfo['status'] === 'approved') {
            \Log::info("Pagamento [{$dataId}] confirmado como APROVADO.", [
                'status' => $paymentInfo['status']
            ]);

            // Agora, vamos achar e atualizar nosso pedido no banco de dados.
            // **IMPORTANTE**: Assumindo que você salva o ID do pagamento do MP na sua tabela de pedidos.
            // Se o nome da coluna for diferente, ajuste a linha abaixo.
            // $pedido = Order::where('mercado_pago_payment_id', $dataId)->first();

            // if ($pedido) {
            //     // Se o pedido já não estiver pago, atualizamos.
            //     if ($pedido->status !== 'pago') {
            //         $pedido->status = 'pago';
            //         $pedido->save();
            //         \Log::info("SUCESSO: Pedido #{$pedido->id} atualizado para 'pago'.");
            //         // AQUI VOCÊ PODE DISPARAR E-MAILS, NOTIFICAÇÕES, DAR BAIXA EM ESTOQUE, ETC.
            //     } else {
            //         \Log::info("Pedido #{$pedido->id} já estava como 'pago'. Nenhuma ação necessária.");
            //     }
            // } else {
            //     // Isso é um alerta CRÍTICO. O dinheiro entrou, mas não achamos o pedido.
            //     \Log::critical("ALERTA: Pagamento [{$dataId}] foi aprovado, mas NENHUM pedido correspondente foi encontrado no banco de dados!");
            // }
        } else {
            // Se o status não for 'approved' (pode ser 'rejected', 'in_process', etc.)
            \Log::info("Pagamento [{$dataId}] com status '{$paymentInfo['status']}'. Nenhuma ação de atualização necessária.");
        }

        return response()->json(['status' => 'webhook processed'], 200);
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
