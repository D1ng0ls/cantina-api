<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ApiCategoriaController extends Controller
{
    /**
     * Listar categorias paginadas (50 por página).
     * 
     * @authenticated
     * @group Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nome": "Bebidas"
     *     }
     *   ],
     *   ...
     * }
     */
    public function show()
    {
        return response()->json(Categoria::paginate(50));
    }

    /**
     * Cadastrar nova categoria.
     * 
     * @authenticated
     * @group Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam nome string required Nome da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria cadastrada com sucesso"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('patron');

        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        Categoria::create([
            'nome' => $request->nome,
        ]);

        return response()->json([
            'success' => 'Categoria cadastrada com sucesso',
        ]);
    }

    /**
     * Atualizar categoria.
     * 
     * @authenticated
     * @group Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam categoria int required ID da categoria.
     * @bodyParam nome string required Nome da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria atualizada com sucesso"
     * }
     */
    public function update(Request $request, Categoria $categoria)
    {
        $this->authorize('patron');

        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $categoria->update([
            'nome' => $request->nome,
        ]);

        return response()->json([
            'success' => 'Categoria atualizada com sucesso',
        ]);
    }

    /**
     * Excluir categoria.
     * 
     * @authenticated
     * @group Categorias
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam categoria int required ID da categoria.
     * 
     * @response 200 {
     *   "success": "Categoria excluída com sucesso"
     * }
     */
    public function destroy(Categoria $categoria)
    {
        $this->authorize('patron');

        $categoria->delete();

        return response()->json([
            'success' => 'Categoria excluída com sucesso',
        ]);
    }
}
