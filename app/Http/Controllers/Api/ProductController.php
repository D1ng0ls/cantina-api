<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Listar produtos ativos com suas categorias (paginado 50 por vez).
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
        return response()->json(Product::where('active', true)->with('category')->paginate(50));
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
    public function show(Product $product)
    {
        if (!$product->active) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        return response()->json($product->load('category'));
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
     * @bodyParam image file nullable Imagem do produto.
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();

            $filename = Str::uuid()->toString() . '.' . $extension;

            $path = $request->file('image')->move(public_path('upload/produtos'), $filename);
        }

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $path ?? null,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'category_id' => $request->category_id,
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
     * @bodyParam image string nullable file Imagem do produto.
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

        if (!$request->active) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'active' => 'required|boolean',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($request->hasFile('image')) {
            $extension = $request->file('image')->getClientOriginalExtension();

            $filename = Str::uuid()->toString() . '.' . $extension;

            $path = $request->file('image')->move(public_path('upload/produtos'), $filename);
        }

        $data = $request->only(['name', 'description', 'price', 'quantity', 'active', 'category_id']);
        $data['imagem'] = $path ?? $product->image;

        $product->update($data);

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
        ]);
    }

    /**
     * Remover imagem do produto.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam product int required ID do produto.
     * 
     * @response 200 {
     *   "message": "Imagem removida com sucesso"
     * }
     */
    public function removeImage(Product $product)
    {
        $this->authorize('patron');

        if (!$product->active) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->update([
            'image' => null,
        ]);

        return response()->json([
            'message' => 'Imagem removida com sucesso',
        ]);
    }

    /**
     * Ativar/desativar produto.
     * 
     * @authenticated
     * @group 4. Produtos
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam product int required ID do produto.
     * 
     * @response 200 {
     *   "message": "Produto ativado/desativado com sucesso"
     * }
     */
    public function toggleActive(Product $product)
    {
        $this->authorize('patron');

        $product->update([
            'active' => !$product->active,
        ]);

        return response()->json([
            'message' => 'Produto ativado/desativado com sucesso',
        ]);
    }

    /**
     * Listar produtos inativos com suas categorias (paginado 50 por vez).
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
    public function indexInactive()
    {
        return response()->json(
            Product::with('category')
                ->where('active', false)
                ->orderBy('name')
                ->paginate(50)
        );
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

        if (!$product->active) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produto excluído com sucesso',
        ]);
    }
}
