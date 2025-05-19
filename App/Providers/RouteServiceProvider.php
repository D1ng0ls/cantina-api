<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * O namespace da aplicação. Pode ser usado pra prefixar os controllers nas rotas.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers\\Api';

    /**
     * Método principal chamado durante o boot.
     */
    public function boot(): void
    {
        parent::boot();

        // Rotas da API (sem sessão, sem CSRF)
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Rotas Web (sessão, CSRF, etc)
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
