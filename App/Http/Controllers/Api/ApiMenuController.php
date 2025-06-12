<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Categoria;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ApiMenuController extends Controller
{
    /**
     * Listar produtos com suas categorias (paginado 50 por vez).
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome": "Produto X",
     *       "categoria": {
     *         "id": 3,
     *         "nome": "Categoria Y"
     *       }
     *     },
     *     ...
     *   ],
     *   ...
     * }
     */
    public function show()
    {
        return response()->json(Produto::with('categoria')->paginate(50));
    }

    /**
     * Mostrar produto pelo ID com categoria.
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam produto int required ID do produto. Exemplo: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "nome": "Produto X",
     *   "categoria": {
     *     "id": 3,
     *     "nome": "Categoria Y"
     *   }
     * }
     */
    public function showById(Produto $produto)
    {
        return response()->json($produto->load('categoria'));
    }

    /**
     * Mostrar produtos filtrando pela categoria (paginado 50 por vez).
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam categoria int required ID da categoria.
     * 
     * @response 200 {
     *   "id": 3,
     *   "nome": "Categoria Y",
     *   "produtos": [
     *     {
     *       "id": 1,
     *       "nome": "Produto X",
     *       ...
     *     },
     *     ...
     *   ]
     * }
     */
    public function showByCategoria(Categoria $categoria)
    {
        return response()->json($categoria->load('produtos')->paginate(50));
    }

    /**
     * Cadastrar novo produto.
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam nome string required Nome do produto.
     * @bodyParam descricao string required Descrição do produto.
     * @bodyParam imagem string required URL da imagem.
     * @bodyParam preco float required Preço do produto.
     * @bodyParam quantidade int required Quantidade disponível.
     * @bodyParam categoria_id int required ID da categoria.
     * 
     * @response 200 {
     *   "success": "Produto cadastrado com sucesso"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('patron');

        $request->validate([
            'nome' => 'required|string',
            'descricao' => 'required|string',
            'imagem' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'preco' => 'required|numeric',
            'quantidade' => 'required|integer',
            //'ativo' =>'required|boolean',
            'categoria_id' => 'required|exists:categorias,id',
        ]);

        $extension = $request->file('imagem')->getClientOriginalExtension();

        // GERAR UUID
        $filename = Str::uuid()->toString() . '.' . $extension;

        // SALVAR no public/upload/produtos
        $path = $request->file('imagem')->move(public_path('upload/produtos'), $filename);

        Produto::create([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'imagem' => 'upload/produtos/' . $filename,
            'preco' => $request->preco,
            'quantidade' => $request->quantidade,
            //'ativo' => $request->ativo,
            'categoria_id' => $request->categoria_id,
        ]);

        return response()->json([
            'success' => 'Produto cadastrado com sucesso',
        ]);
    }

    /**
     * Atualizar produto.
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam produto int required ID do produto.
     * 
     * @bodyParam nome string required Nome do produto.
     * @bodyParam descricao string required Descrição do produto.
     * @bodyParam imagem file required Imagem do produto.
     * @bodyParam preco float required Preço do produto.
     * @bodyParam quantidade int required Quantidade disponível.
     * @bodyParam categoria_id int required ID da categoria.
     * 
     * @response 200 {
     *   "message": "Produto atualizado com sucesso"
     * }
     */
    public function update(Request $request, Produto $produto)
    {
        $this->authorize('patron');

        $request->validate([
            'nome' => 'required|string',
            'descricao' => 'required|string',
            'imagem' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'preco' => 'required|numeric',
            'quantidade' => 'required|integer',
            //'ativo' =>'required|boolean',
            'categoria_id' => 'required|exists:categorias,id',
        ]);

        $extension = $request->file('imagem')->getClientOriginalExtension();

        // GERAR UUID
        $filename = Str::uuid()->toString() . '.' . $extension;

        // SALVAR no public/upload/produtos
        $path = $request->file('imagem')->move(public_path('upload/produtos'), $filename);

        // MONTA os dados
        $data = $request->only(['nome', 'descricao', 'preco', 'quantidade', 'categoria_id']);
        $data['imagem'] = 'upload/produtos/' . $filename;

        $produto->update($data);

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
        ]);
    }

    /**
     * Excluir produto.
     * 
     * @authenticated
     * @group Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam produto int required ID do produto.
     * 
     * @response 200 {
     *   "message": "Produto excluído com sucesso"
     * }
     */
    public function destroy(Produto $produto)
    {
        $this->authorize('patron');

        $produto->delete();

        return response()->json([
            'message' => 'Produto excluído com sucesso',
        ]);
    }
}
