<?php

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoService
{
    protected PreferenceClient $preferenceClient;
    protected PaymentClient $paymentClient;

    public function __construct()
    {
        $this->preferenceClient = new PreferenceClient();
        $this->paymentClient = new PaymentClient();
    }

    public function createOrder(array $dados)
    {
        return $this->preferenceClient->create([
            'items' => $dados['items'],
            'payer' => $dados['payer'],
            'notification_url' => $dados['notification_url'] ?? null,
            'payment_methods' => [
                'excluded_payment_types' => [
                    ['id' => 'ticket'],
                    ['id' => 'atm'],
                ],
                'installments' => 1,
            ],
        ]);
    }

    public function getPaymentStatus(string $paymentId)
    {
        return $this->paymentClient->get($paymentId);
    }
}
