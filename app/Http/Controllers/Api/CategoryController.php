<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    /**
     * Listar categorias com seus produtos.
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "categories": [
     *     {
     *       "id": 3,
     *       "name": "Categoria Y",
     *       "products": [
     *         {
     *           "id": 1,
     *           "name": "Produto X",
     *           ...
     *         },
     *         ...
     *       ]
     *     },
     *     ...
     *   ]
     * }
     */
    public function index()
    {
        return response()->json(
            Category::with(['products' => function ($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get()
        );
    }

    /**
     * Mostrar produtos filtrando pela categoria (paginado 50 por vez).
     * 
     * @authenticated
     * @group 3. Categorias
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
    public function show(Category $category)
    {
        $category->load(['products' => function ($query) {
            $query->paginate(50);
        }]);

        return response()->json($category);
    }

    /**
     * Cadastrar nova categoria.
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam name string required Nome da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria cadastrada com sucesso"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('patron');

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => 'Categoria cadastrada com sucesso',
        ]);
    }

    /**
     * Atualizar categoria.
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam categoria int required ID da categoria.
     * @bodyParam name string required Nome da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria atualizada com sucesso"
     * }
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('patron');

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => 'Categoria atualizada com sucesso',
        ]);
    }

    /**
     * Excluir categoria.
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam categoria int required ID da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria excluída com sucesso"
     * }
     */
    public function destroy(Category $category)
    {
        $this->authorize('patron');

        $category->delete();

        return response()->json([
            'success' => 'Categoria excluída com sucesso',
        ]);
    }
}
