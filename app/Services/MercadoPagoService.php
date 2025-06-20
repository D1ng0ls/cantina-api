<?php

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoService
{
    protected PreferenceClient $preferenceClient;
    protected PaymentClient $paymentClient;
    protected $webhookUrl;

    public function __construct()
    {
        $this->preferenceClient = new PreferenceClient();
        $this->paymentClient = new PaymentClient();
        $this->webhookUrl = config('services.mercadopago.webhook_url');
    }

    public function createOrder(array $dados)
    {
        try {
            $response = $this->preferenceClient->create([
            'items' => $dados['items'],
            'payer' => $dados['payer'],
            'notification_url' => 'https://cantina-api.test/api/v1/mercado-pago/webhook',
            'payment_methods' => [
                'excluded_payment_types' => [
                    ['id' => 'ticket'],
                    ['id' => 'atm'],
                ],
                'installments' => 1,
            ],
        ]);
        } catch (\Exception $e) {
            \Log::error('MercadoPago API Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \Exception($e->getMessage());
        }
        
        return $response->init_point;
    }

    public function getPaymentStatus(string $paymentId)
    {
        return $this->paymentClient->get($paymentId);
    }
}
