<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\OpeningHour;

class OpeningHourController extends ApiController
{
    /**
     * Listar horarios de funcionamento.
     * 
     * @authenticated
     * @group 6. Horários
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "day": 0,
     *       "open": "08:00:00",
     *       "close": "18:00:00"
     *     }
     *   ]
     * }
     */
    public function index()
    {
        return response()->json(OpeningHour::all());
    }

    /**
     * Mostrar horário de funcionamento pelo dia da semana.
     * 
     * @authenticated
     * @group 6. Horários
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam day int required ID dia da semana (0 = Domingo, 1 = Segunda, ...).
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "day": 0,
     *       "open": "08:00:00",
     *       "close": "18:00:00"
     *     }
     *   ]
     * }
     */
    public function showByWeekday(int $day)
    {
        if ($day < 0 || $day > 6) {
            return response()->json(['error' => 'Dia inválido.'], 400);
        }

        $openingHour = OpeningHour::where('day', $day)->get();
        
        return response()->json($openingHour);
    }

    /**
     * Cadastrar novo horário de funcionamento.
     * 
     * @authenticated
     * @permission patron
     * @group 6. Horários
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam day int required Dia da semana (0 = Domingo, 1 = Segunda, ...).
     * @bodyParam open string required Hora de abertura (HH:MM).
     * @bodyParam close string required Hora de fechamento (HH:MM).
     * 
     * @response 200 {
     *   "success": "Horário cadastrado com sucesso"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('patron');

        $request->validate([
            'day' => 'required|integer|between:0,6',
            'open' => 'required|date_format:H:i',
            'close' => 'required|date_format:H:i',
        ]);

        OpeningHour::create([
            'day' => $request->day,
            'open' => $request->open,
            'close' => $request->close,
        ]);

        return response()->json([
            'success' => 'Horário cadastrado com sucesso',
        ]);
    }

    /**
     * Atualizar horário de funcionamento.
     * 
     * @authenticated
     * @permission patron
     * @group 6. Horários
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam opening_hour int required ID do horário.
     * 
     * @bodyParam day int required Dia da semana (0 = Domingo, 1 = Segunda, ...).
     * @bodyParam open string required Hora de abertura (HH:MM).
     * @bodyParam close string required Hora de fechamento (HH:MM).
     * 
     * @response 200 {
     *   "success": "Horário atualizado com sucesso"
     * }
     */
    public function update(Request $request, OpeningHour $openingHour)
    {
        $this->authorize('patron');

        $request->validate([
            'day' => 'required|integer|between:0,6',
            'open' => 'required|date_format:H:i',
            'close' => 'required|date_format:H:i',
        ]);

        $openingHour->update([
            'day' => $request->day,
            'open' => $request->open,
            'close' => $request->close,
        ]);

        return response()->json([
            'success' => 'Horário atualizado com sucesso',
        ]);
    }

    /**
     * Excluir horário de funcionamento.
     * 
     * @authenticated
     * @permission patron
     * @group 6. Horários
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @urlParam opening_hour int required ID do horário.
     * 
     * @response 200 {
     *   "success": "Horário excluído com sucesso"
     * }
     */
    public function destroy(OpeningHour $openingHour)
    {
        $this->authorize('patron');

        $openingHour->delete();

        return response()->json([
            'success' => 'Horário excluído com sucesso',
        ]);
    }
}
