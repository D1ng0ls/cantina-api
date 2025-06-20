<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\DB;

class OrderController extends ApiController
{
    /**
     * Listar pedidos (paginação de 10 em 10).
     * 
     * @authenticated
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
        return response()->json(Order::with(['products', 'user', 'payment'])->paginate(10));
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
        // Validação de dados
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
            $mp_items = []; // Array para os itens do Mercado Pago
            $user = Auth::user();

            // Loop único pra calcular o total e já montar os itens pro MP
            foreach ($request->products as $produto) {
                $produto_data = Product::find($produto['id']);

                // O if de produto não encontrado pode ser removido, pois a validação já faz isso.
                // Mas se quiser manter por segurança extra, sem problemas.

                $calculo_valor_total += $produto_data->price * $produto['quantity'];

                // Adiciona o item no formato que a API do MP espera
                $mp_items[] = [
                    'title' => $produto_data->name, // Supondo que seu produto tenha um campo 'name'
                    'quantity' => (int) $produto['quantity'],
                    'unit_price' => (float) $produto_data->price,
                    'currency_id' => 'BRL' // Moeda
                ];
            }

            // 1. Criando o pedido no seu sistema
            $order = Order::create([
                'total_value' => $calculo_valor_total,
                'user_id' => $user->id,
                'status' => 'awaiting_payment' // Dica: Adicione um status ao pedido!
            ]);

            // 2. Associando os produtos ao pedido (pivot table)
            foreach ($request->products as $produto) {
                $produto_data = Product::find($produto['id']);
                $order->products()->attach($produto['id'], [
                    'quantity' => $produto['quantity'],
                    'value_unitary' => $produto_data->price,
                    // Não precisa mais de 'order_id' e 'product_id', o attach já cuida disso
                ]);
            }

            // 3. Montando os dados para o serviço do Mercado Pago
            $dados_pagamento = [
                'items' => $mp_items,
                'payer' => [
                    'name' => $user->name, // Supondo que seu user tenha 'name'
                    'email' => $user->email,
                ],
                'external_reference' => $order->id, // ISSO É MUITO IMPORTANTE!
            ];

            // 4. Chamando o serviço para criar a preferência de pagamento
            // Aqui eu estou usando a variável injetada no construtor
            $init_point = $mercadoPagoService->createOrder($dados_pagamento);

            // Dica: Salve o ID da preferência do MP no seu pedido pra referência futura
            // $order->mercado_pago_preference_id = $init_point['id']; // O service precisa retornar o array completo
            // $order->save();

            // Se tudo deu certo até aqui, confirma a transação
            DB::commit();

            // 5. Retornando o link de pagamento
            return response()->json([
                'success' => 'Pedido realizado com sucesso, aguardando pagamento.',
                'id_pedido' => $order->id,
                'payment_url' => $init_point, // O seu serviço já retorna o init_point direto
            ]);
        } catch (\Exception $e) {
            // Se qualquer coisa deu errado, desfaz tudo que foi feito no banco
            DB::rollBack();

            // Retorna uma resposta de erro genérica pro usuário
            return response()->json([
                'error' => 'Ocorreu um erro ao processar seu pedido. Tente novamente mais tarde.',
                'details' => $e->getMessage() // Opcional, bom para o dev no frontend
            ], 500);
        }
    }

    /**
     * Atualizar o status de um pedido.
     * 
     * @authenticated
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
    public function update(Request $request, Order $pedido)
    {
        $this->authorize('patron');

        $request->validate([
            'status' => 'required|in:open,awaiting_payment,approved,in_preparation,ready,canceled',
        ], [
            'status.required' => 'O status do pedido é obrigatório.',
            'status.in' => 'O status do pedido deve ser um dos seguintes: open, awaiting_payment, approved, in_preparation, ready, canceled',
        ]);

        $pedido->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => 'Status do pedido atualizado com sucesso',
        ]);
    }

    /**
     * Deletar um pedido.
     * 
     * @authenticated
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
