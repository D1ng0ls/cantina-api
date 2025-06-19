<?php

namespace App\Http\Controllers\Api\WebHook\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;


class MessageController extends Controller
{
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