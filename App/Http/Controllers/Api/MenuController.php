<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class MenuController extends ApiController
{
    /**
     * Listar produtos com suas categorias (paginado 50 por vez).
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Produto X",
     *       "category": {
     *         "id": 3,
     *         "name": "Categoria Y"
     *       }
     *     },
     *     ...
     *   ],
     *   ...
     * }
     */
    public function index()
    {
        return response()->json(Product::with('category')->paginate(50));
    }

    /**
     * Mostrar produto pelo ID com categoria.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam product int required ID do produto. Exemplo: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "name": "Produto X",
     *   "category": {
     *     "id": 3,
     *     "name": "Categoria Y"
     *   }
     * }
     */
    public function showById(Product $product)
    {
        return response()->json($product->load('categoria'));
    }

    /**
     * Mostrar produtos filtrando pela categoria (paginado 50 por vez).
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam category int required ID da categoria.
     * 
     * @response 200 {
     *   "id": 3,
     *   "name": "Categoria Y",
     *   "products": [
     *     {
     *       "id": 1,
     *       "name": "Produto X",
     *       ...
     *     },
     *     ...
     *   ]
     * }
     */
    public function showByCategoria(Category $category)
    {
        return response()->json($category->load('products')->paginate(50));
    }

    /**
     * Cadastrar novo produto.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam name string required Nome do produto.
     * @bodyParam description string required Descrição do produto.
     * @bodyParam image string required URL da imagem.
     * @bodyParam price float required Preço do produto.
     * @bodyParam quantity int required Quantidade disponível.
     * @bodyParam category_id int required ID da categoria.
     * 
     * @response 200 {
     *   "success": "Produto cadastrado com sucesso"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('patron');

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'category_id' => 'required|exists:categorias,id',
        ]);

        $extension = $request->file('image')->getClientOriginalExtension();

        // GERAR UUID
        $filename = Str::uuid()->toString() . '.' . $extension;

        // SALVAR no public/upload/produtos
        $path = $request->file('image')->move(public_path('upload/produtos'), $filename);

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => 'upload/produtos/' . $filename,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'category_id' => $request->categoria_id,
        ]);

        return response()->json([
            'success' => 'Produto cadastrado com sucesso',
        ]);
    }

    /**
     * Atualizar produto.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam produto int required ID do produto.
     * 
     * @bodyParam name string required Nome do produto.
     * @bodyParam description string required Descrição do produto.
     * @bodyParam image string required URL da imagem.
     * @bodyParam price float required Preço do produto.
     * @bodyParam quantity int required Quantidade disponível.
     * @bodyParam category_id int required ID da categoria.
     * 
     * @response 200 {
     *   "message": "Produto atualizado com sucesso"
     * }
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('patron');

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'active' =>'required|boolean',
            'category_id' => 'required|exists:categorias,id',
        ]);

        $extension = $request->file('imagem')->getClientOriginalExtension();

        // GERAR UUID
        $filename = Str::uuid()->toString() . '.' . $extension;

        // SALVAR no public/upload/produtos
        $path = $request->file('imagem')->move(public_path('upload/produtos'), $filename);

        // MONTA os dados
        $data = $request->only(['name', 'description', 'price', 'quantity', 'active', 'category_id']);
        $data['imagem'] = 'upload/produtos/' . $filename;

        $product->update($data);

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
        ]);
    }

    /**
     * Excluir produto.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam product int required ID do produto.
     * 
     * @response 200 {
     *   "message": "Produto excluído com sucesso"
     * }
     */
    public function destroy(Product $product)
    {
        $this->authorize('patron');

        $product->delete();

        return response()->json([
            'message' => 'Produto excluído com sucesso',
        ]);
    }
}
