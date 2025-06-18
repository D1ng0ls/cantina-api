<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

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

        $calculo_valor_total = 0;

        // Cálculo do valor total com base no valor do banco
        foreach ($request->products as $produto) {
            $produto_data = Product::find($produto['id']);

            if (!$produto_data) {
                return response()->json([
                    'error' => 'Produto não encontrado.',
                ], 404);
            }

            // Calculando o valor total
            $calculo_valor_total += $produto_data->price * $produto['quantity'];
        }

        // Criando o pedido
        $order = Order::create([
            'total_value' => $calculo_valor_total,
            'user_id' => Auth::user()->id,
        ]);

        // Associando os produtos ao pedido
        foreach ($request->products as $produto) {
            $produto_data = Product::find($produto['id']);

            if (!$produto_data) {
                return response()->json([
                    'error' => 'Produto não encontrado.',
                ], 404);
            }

            // Associando os produtos ao pedido com quantidade e valor unitário
            $order->products()->attach($produto['id'], [
                'quantity' => $produto['quantity'],
                'value_unitary' => $produto_data->price,
                'order_id' => $order->id,
                'product_id' => $produto['id'],
            ]);
        }

        // Retornando sucesso com o id do pedido
        return response()->json([
            'success' => 'Pedido realizado com sucesso',
            'id_pedido' => $order->id,  // Enviando o id do pedido criado
        ]);
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
