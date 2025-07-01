<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class MenuController extends Controller
{
    /**
     * Listar categorias com seus produtos.
     * 
     * @authenticated
     * @group 5. Menu
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
}
