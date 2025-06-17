<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    /**
     * Listar categorias paginadas (50 por página).
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Bebidas"
     *     }
     *   ],
     *   ...
     * }
     */
    public function index()
    {
        return response()->json(Category::paginate(50));
    }

    /**
     * Listar produtos de uma categoria.
     * 
     * @authenticated
     * @group 3. Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam category int required ID da categoria.
     * 
     * @response 200 {
     *   "category_name": "Bebidas",
     *   "products": [
     *     {
     *       "id": 1,
     *       "name": "Coca-Cola",
     *       "preco": 5.00
     *     }
     *   ]
     * }
     */
    public function showById(Category $category)
    {
        return response()->json([
            'category_name' => $category->name,
            'products' => $category->products()->paginate(50),
        ]);
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
