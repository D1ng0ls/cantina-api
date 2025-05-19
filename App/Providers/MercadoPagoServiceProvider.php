<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
    }

    public function register() {}
}
