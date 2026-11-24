<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SensorColmena;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SensorColmenaController extends Controller
{
    /**
     * 1. VER LISTADO (Carga a la grilla con búsqueda y límite)
     * Endpoint: GET /api/v1/colmenas
     */
    public function index(Request $request): JsonResponse
    {
        $query = SensorColmena::query();

        // 1. Filtro de búsqueda con agrupación lógica (Logical Grouping)
        if ($request->filled('search')) {
            $criterio = trim((string) $request->input('search'));

            $query->where(function ($subQuery) use ($criterio) {
                $subQuery->where('apiario', 'LIKE', "%{$criterio}%")
                         ->orWhere('mensaje_alerta', 'LIKE', "%{$criterio}%")
                         ->orWhere('id_sensor', $criterio);
            });
        }

        // 2. Control estricto de límite (entre 1 y 100 para evitar desbordes o limit=0)
        $limit = max(1, min((int) $request->integer('limit', 20), 100));

        // 3. Ejecución de la consulta
        $colmenas = $query->orderBy('id_sensor', 'desc')
                          ->take($limit)
                          ->get();

        return response()->json([
            'estado' => 'exito',
            'total'  => $colmenas->count(),
            'datos'  => $colmenas,
        ], Response::HTTP_OK);
    }

    /**
     * 2. VER DETALLE (Búsqueda por clave primaria)
     * Endpoint: GET /api/v1/colmenas/{id}
     */
    public function show(string $id): JsonResponse
    {
        $sensor = SensorColmena::find($id);

        if (!$sensor) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => "La lectura con identificador '{$id}' no fue localizada en el sistema.",
                'datos'   => null,
            ], 404);
        }

        return response()->json([
            'estado'  => 'exito',
            'mensaje' => 'Lectura de colmena localizada correctamente.',
            'datos'   => $sensor,
        ], 200);
    }

    /**
     * 3. REGISTRAR (Creación con validación estricta y cálculo de umbral)
     * Endpoint: POST /api/v1/colmenas
     */
    public function store(Request $request): JsonResponse
    {
        $validados = $request->validate([
            'apiario'     => ['required', 'string', 'max:100'],
            'temperatura' => ['required', 'numeric', 'between:-10,70'],
            'humedad'     => ['required', 'numeric', 'between:0,100'],
        ], [
            'apiario.required'     => 'El nombre del apiario es obligatorio.',
            'apiario.max'          => 'El nombre del apiario no debe superar los 100 caracteres.',
            'temperatura.required' => 'La temperatura es obligatoria.',
            'temperatura.numeric'  => 'La temperatura debe ser un valor numérico.',
            'temperatura.between'  => 'La temperatura debe estar entre -10 °C y 70 °C.',
            'humedad.required'     => 'La humedad es obligatoria.',
            'humedad.numeric'      => 'La humedad debe ser un valor numérico.',
            'humedad.between'      => 'La humedad debe estar entre 0 % y 100 %.',
        ]);

        // Evaluación condicional de los 3 umbrales de microclima
        $temp = (float) $validados['temperatura'];

        if ($temp < 32.0) {
            $validados['mensaje_alerta'] = 'ALERTA: ENFRIAMIENTO DE CRIA';
        } elseif ($temp >= 32.0 && $temp <= 36.0) {
            $validados['mensaje_alerta'] = 'MICROCLIMA OPTIMO';
        } else {
            $validados['mensaje_alerta'] = 'ALERTA: ESTRES TERMICO';
        }

        $sensor = SensorColmena::create($validados);

        return response()->json([
            'estado'  => 'exito',
            'mensaje' => 'Lectura registrada satisfactoriamente.',
            'datos'   => $sensor,
        ], 201);
    }

    /**
     * 4. ACTUALIZAR (Modificación controlada y recálculo de umbral)
     * Endpoint: PUT /api/v1/colmenas/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $sensor = SensorColmena::find($id);

        if (!$sensor) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => "No se puede editar: la lectura con identificador '{$id}' no existe.",
                'datos'   => null,
            ], 404);
        }

        $validados = $request->validate([
            'apiario'     => ['required', 'string', 'max:100'],
            'temperatura' => ['required', 'numeric', 'between:-10,70'],
            'humedad'     => ['required', 'numeric', 'between:0,100'],
        ], [
            'apiario.required'     => 'El nombre del apiario es obligatorio.',
            'apiario.max'          => 'El nombre del apiario no debe superar los 100 caracteres.',
            'temperatura.required' => 'La temperatura es obligatoria.',
            'temperatura.numeric'  => 'La temperatura debe ser un valor numérico.',
            'temperatura.between'  => 'La temperatura debe estar entre -10 °C y 70 °C.',
            'humedad.required'     => 'La humedad es obligatoria.',
            'humedad.numeric'      => 'La humedad debe ser un valor numérico.',
            'humedad.between'      => 'La humedad debe estar entre 0 % y 100 %.',
        ]);

        // Recálculo condicional de los 3 umbrales al modificar temperatura
        $temp = (float) $validados['temperatura'];

        if ($temp < 32.0) {
            $validados['mensaje_alerta'] = 'ALERTA: ENFRIAMIENTO DE CRIA';
        } elseif ($temp >= 32.0 && $temp <= 36.0) {
            $validados['mensaje_alerta'] = 'MICROCLIMA OPTIMO';
        } else {
            $validados['mensaje_alerta'] = 'ALERTA: ESTRES TERMICO';
        }

        $sensor->update($validados);

        return response()->json([
            'estado'  => 'exito',
            'mensaje' => 'Lectura actualizada satisfactoriamente.',
            'datos'   => $sensor,
        ], 200);
    }

    /**
     * 5. ELIMINAR (Baja física directa de MySQL)
     * Endpoint: DELETE /api/v1/colmenas/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $sensor = SensorColmena::find($id);

        if (!$sensor) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => "No se puede eliminar: la lectura con identificador '{$id}' no existe.",
                'datos'   => null,
            ], 404);
        }

        $sensor->delete();

        return response()->json([
            'estado'  => 'exito',
            'mensaje' => "La lectura del apiario '{$sensor->apiario}' fue eliminada físicamente del sistema.",
            'datos'   => null,
        ], 200);
    }
}