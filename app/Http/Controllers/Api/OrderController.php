<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\MercadoPagoService;
use App\Models\Order;
use App\Models\Product;

class OrderController extends ApiController
{
    /**
     * Listar pedidos (paginação de 10 em 10).
     * 
     * @authenticated
     * @permission patron
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": 
     *   [
     *     {
     *       "id": 1,
     *       "valor_total": 100,
     *       "status": "aberto",
     *       "usuario": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "pagamento": [
     *         {
     *           "id": 1,
     *           "status": "aprovado"
     *         }
     *       ],
     *       "produtos": [
     *         {
     *           "id": 1,
     *           "nome": "Produto X",
     *           "quantidade": 1,
     *           "valor_unitario": 100
     *         },
     *         ...
     *       ]
     *     }
     *   ],
     *   ...
     * }
     */
    public function index()
    {
        $this->authorize('patron');

        return response()->json(Order::with(['products', 'user', 'payment'])->paginate(50));
    }

    /**
     * Mostrar pedido pelo ID.
     * 
     * @authenticated
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam order int required ID do pedido.
     * 
     * @response 200 {
     *   "id": 1,
     *   "valor_total": 100,
     *   "status": "aberto",
     *   "usuario": {
     *     "id": 1,
     *     "name": "John Doe"
     *   },
     *   "pagamento": [
     *     {
     *       "id": 1,
     *       "status": "aprovado"
     *     }
     *   ],
     *   "produtos": [
     *     {
     *       "id": 1,
     *       "nome": "Produto X",
     *       "quantidade": 1,
     *       "valor_unitario": 100
     *     },
     *     ...
     *   ]
     * }
     */
    public function show(Order $order)
    {
        return response()->json($order->load(['products', 'user', 'payment']));
    }

    /**
     * Mostrar pedidos por usuário.
     * 
     * @authenticated
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "valor_total": 100,
     *       "status": "aberto",
     *       "usuario": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "pagamento": [
     *         {
     *           "id": 1,
     *           "status": "aprovado"
     *         }
     *       ],
     *       "produtos": [
     *         {
     *           "id": 1,
     *           "nome": "Produto X",
     *           "quantidade": 1,
     *           "valor_unitario": 100
     *         },
     *         ...
     *       ]
     *     }
     *   ]
     * }
     */
    public function showByUser(Request $request)
    {
        return response()->json(auth()->user()->orders()->with(['products', 'payment']));
    }

    /**
     * Criar um novo pedido com produtos.
     * 
     * @authenticated
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam produtos array required Lista de produtos.
     * @bodyParam produtos[].id int required ID do produto.
     * @bodyParam produtos[].quantidade int required Quantidade do produto.
     * 
     * @response 200 {
     *   "success": "Pedido realizado com sucesso",
     *   "id_pedido": 1
     * }
     * 
     * @response 404 {
     *   "error": "Produto não encontrado."
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric',
        ], [
            'products.required' => 'Lista de produtos é obrigatória.',
            'products.array' => 'Lista de produtos deve ser um array.',
            'products.*.id.required' => 'ID do produto é obrigatório.',
            'products.*.id.exists' => 'Produto não encontrado.',
            'products.*.quantity.required' => 'Quantidade do produto é obrigatória.',
            'products.*.quantity.numeric' => 'Quantidade do produto deve ser um número.',
        ]);

        $mercadoPagoService = app(MercadoPagoService::class);

        DB::beginTransaction();

        try {
            $calculo_valor_total = 0;
            $mp_items = [];
            $user = Auth::user();

            foreach ($request->products as $produto) {
                $produto_data = Product::find($produto['id']);

                $calculo_valor_total += $produto_data->price * $produto['quantity'];

                $mp_items[] = [
                    'title' => $produto_data->name,
                    'quantity' => (int) $produto['quantity'],
                    'unit_price' => (float) $produto_data->price,
                    'currency_id' => 'BRL'
                ];
            }

            $order = Order::create([
                'total_value' => $calculo_valor_total,
                'user_id' => $user->id,
                'status' => 'awaiting_payment'
            ]);

            foreach ($request->products as $produto) {
                $produto_data = Product::where('id', $produto['id'])->lockForUpdate()->first();
                $order->products()->attach($produto['id'], [
                    'quantity' => $produto['quantity'],
                    'value_unitary' => $produto_data->price,
                ]);

                if (!$produto_data || $produto_data->quantity < $produto['quantity']) {
                    throw new \Exception('Produto ' . ($produto_data->name ?? $produto['id']) . ' com quantidade insuficiente em estoque.');
                }

                $produto_data->quantity -= $produto['quantity'];
                $produto_data->save();
            }

            $dados_pagamento = [
                'items' => $mp_items,
                'payer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'external_reference' => $order->id,
            ];

            $init_point = $mercadoPagoService->createOrder($dados_pagamento);

            // $order->mercado_pago_preference_id = $init_point['id']
            // $order->save();

            DB::commit();

            return response()->json([
                'success' => 'Pedido realizado com sucesso, aguardando pagamento.',
                'id_pedido' => $order->id,
                'payment_url' => $init_point,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Ocorreu um erro ao processar seu pedido. Tente novamente mais tarde.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar o status de um pedido.
     * 
     * @authenticated
     * @permission patron
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam pedido int required ID do pedido.
     * @bodyParam status string required Novo status. Valores possíveis: open, awaiting_payment, approved, in_preparation, ready, canceled.
     * 
     * @response 200 {
     *   "success": "Status do pedido atualizado com sucesso"
     * }
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('patron');

        DB::beginTransaction();
        try {
            $order->update([
                'status' => $request->status,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Ocorreu um erro ao atualizar o status do pedido. Tente novamente mais tarde.',
                'details' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => 'Status do pedido atualizado com sucesso',
        ]);
    }

    /**
     * Deletar um pedido.
     * 
     * @authenticated
     * @permission patron
     * @group 5. Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam pedido int required ID do pedido.
     * 
     * @response 200 {
     *   "success": "Pedido removido com sucesso"
     * }
     */
    public function destroy(Order $pedido)
    {
        $this->authorize('patron');

        $pedido->delete();

        return response()->json([
            'success' => 'Pedido removido com sucesso',
        ]);
    }
}