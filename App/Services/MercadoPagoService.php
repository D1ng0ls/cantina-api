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
        // dados mínimos esperados: items, payer, notification_url (opcional)
        return $this->preferenceClient->create([
            'items' => $dados['items'],
            'payer' => $dados['payer'],
            'notification_url' => $dados['notification_url'] ?? null,
            'payment_methods' => [
                'excluded_payment_types' => [
                    ['id' => 'ticket'], // Boleto
                    ['id' => 'atm'],    // Caixa eletrônico
                    // NÃO INCLUIR 'account_money' aqui
                ],
                'installments' => 1, // Se quiser limitar a 1x no cartão
            ],
        ]);
    }

    public function getPaymentStatus(string $paymentId)
    {
        return $this->paymentClient->get($paymentId);
    }
}
