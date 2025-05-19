<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;

class ApiPedidoController extends Controller
{
    /**
     * Listar pedidos (paginação de 10 em 10).
     * 
     * @authenticated
     * @group Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": [...],
     *   ...
     * }
     */
    public function show()
    {
        return response()->json(Pedido::paginate(10));
    }

    /**
     * Criar um novo pedido com produtos.
     * 
     * @authenticated
     * @group Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam valor_total float required Valor total calculado.
     * @bodyParam produtos array required Lista de produtos.
     * @bodyParam produtos[].id int required ID do produto.
     * @bodyParam produtos[].quantidade int required Quantidade do produto.
     * 
     * @response 200 {
     *   "success": "Pedido realizado com sucesso",
     *   "id_pedido": 1
     * }
     * 
     * @response 400 {
     *   "error": "O valor total calculado não corresponde ao valor informado."
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
            'valor_total' => 'required|numeric',
            'produtos' => 'required|array',
            'produtos.*.id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|numeric',
        ]);

        $calculo_valor_total = 0;

        // Cálculo do valor total com base no valor do banco
        foreach ($request->produtos as $produto) {
            $produto_data = Produto::find($produto['id']);

            if (!$produto_data) {
                return response()->json([
                    'error' => 'Produto não encontrado.',
                ], 404);
            }

            // Calculando o valor total
            $calculo_valor_total += $produto_data->preco * $produto['quantidade'];
        }

        // Verificando se o valor total informado confere com o calculado
        if ($calculo_valor_total != $request->valor_total) {
            return response()->json([
                'error' => 'O valor total calculado não corresponde ao valor informado.',
            ], 400);
        }

        // Criando o pedido
        $pedido = Pedido::create([
            'valor_total' => $calculo_valor_total,
            'usuario_id' => Auth::user()->id,
        ]);

        // Associando os produtos ao pedido
        foreach ($request->produtos as $produto) {
            $produto_data = Produto::find($produto['id']);

            if (!$produto_data) {
                return response()->json([
                    'error' => 'Produto não encontrado.',
                ], 404);
            }

            // Associando os produtos ao pedido com quantidade e valor unitário
            $pedido->produtos()->attach($produto['id'], [
                'quantidade' => $produto['quantidade'],
                'valor_unitario' => $produto_data->preco,
                'pedido_id' => $pedido->id,
                'produto_id' => $produto['id'],
            ]);
        }

        // Retornando sucesso com o id do pedido
        return response()->json([
            'success' => 'Pedido realizado com sucesso',
            'id_pedido' => $pedido->id,  // Enviando o id do pedido criado
        ]);
    }

    /**
     * Atualizar o status de um pedido.
     * 
     * @authenticated
     * @group Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam pedido int required ID do pedido.
     * @bodyParam status string required Novo status. Valores possíveis: aberto, aguardando_pagamento, aprovado, em_preparacao, pronto, cancelado.
     * 
     * @response 200 {
     *   "success": "Status do pedido atualizado com sucesso"
     * }
     */
    public function update(Request $request, Pedido $pedido)
    {
        $this->authorize('patron');

        $request->validate([
            'status' => 'required|in:aberto,aguardando_pagamento,aprovado,em_preparacao,pronto,cancelado'
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
     * @group Pedidos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam pedido int required ID do pedido.
     * 
     * @response 200 {
     *   "success": "Pedido removido com sucesso"
     * }
     */
    public function destroy(Pedido $pedido)
    {
        $this->authorize('patron');

        $pedido->delete();

        return response()->json([
            'success' => 'Pedido removido com sucesso',
        ]);
    }
}
