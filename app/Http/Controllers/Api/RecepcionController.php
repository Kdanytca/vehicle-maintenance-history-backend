<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Recepcion;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * G2DS-16: registrar, consultar y corregir la recepción de un vehículo.
 */
class RecepcionController extends Controller
{
    /**
     * Reglas de validación compartidas con el registro de vehículos.
     */
    public static function reglas(bool $obligatorio = true): array
    {
        $requerido = $obligatorio ? 'required' : 'nullable';

        return [
            'kilometraje' => [$requerido, 'integer', 'min:0', 'max:9999999'],
            'nivel_combustible' => [$obligatorio ? 'required' : 'required_with:kilometraje', 'nullable', Rule::in(Recepcion::NIVELES_COMBUSTIBLE)],
            'danos_visibles' => ['nullable', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Crea la recepción. La fecha se toma del servidor, no del usuario.
     */
    public static function registrar(Vehiculo $vehiculo, array $data, ?int $idUsuario): Recepcion
    {
        $ultima = $vehiculo->recepciones()->orderByDesc('fecha_recepcion')->orderByDesc('id_recepcion')->first();

        if ($ultima && (int) $data['kilometraje'] < $ultima->kilometraje) {
            throw ValidationException::withMessages([
                'kilometraje' => "El kilometraje no puede ser menor al de la última recepción ({$ultima->kilometraje} km).",
            ]);
        }

        $recepcion = $vehiculo->recepciones()->create([
            'kilometraje' => $data['kilometraje'],
            'nivel_combustible' => $data['nivel_combustible'],
            'danos_visibles' => $data['danos_visibles'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'fecha_recepcion' => now(),
            'id_usuario' => $idUsuario,
        ]);

        AuditLog::registrar(
            'crear',
            'recepciones',
            "Se registró la recepción del vehículo {$vehiculo->placa}: {$recepcion->kilometraje} km, combustible {$recepcion->nivel_combustible}"
        );

        return $recepcion;
    }

    /**
     * Lista las recepciones del vehículo, de la más reciente a la más antigua.
     * Cada una indica si el usuario actual puede corregirla.
     */
    public function index(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::withTrashed()->with('propietario')->findOrFail($vehiculo);
        $usuario = $request->user()->loadMissing('rol');

        $recepciones = $vehiculo->recepciones()
            ->with(['usuario:id_usuario,username', 'editor:id_usuario,username'])
            ->orderByDesc('fecha_recepcion')
            ->orderByDesc('id_recepcion')
            ->get()
            ->each(fn (Recepcion $r) => $r->setAttribute('puede_editar', $r->puedeEditar($usuario)));

        return response()->json([
            'vehiculo' => $vehiculo,
            'recepciones' => $recepciones,
            'niveles_combustible' => Recepcion::NIVELES_COMBUSTIBLE,
        ]);
    }

    /**
     * Registra una nueva recepción para un vehículo que ya existe.
     */
    public function store(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::findOrFail($vehiculo);
        $data = $request->validate(self::reglas());

        $recepcion = self::registrar($vehiculo, $data, $request->user()?->id_usuario);

        return response()->json($recepcion->load('usuario:id_usuario,username'), 201);
    }

    /**
     * Corrige una recepción. Solo el administrador (ruta protegida con solo.admin),
     * solo el mismo día en que se registró y con un motivo obligatorio.
     * La fecha de recepción nunca se cambia.
     */
    public function update(Request $request, int $vehiculo, int $recepcion): JsonResponse
    {
        $data = $request->validate(self::reglas() + [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motivo.required' => 'Debes indicar el motivo de la modificación.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        return DB::transaction(function () use ($request, $vehiculo, $recepcion, $data): JsonResponse {
            $recepcion = Recepcion::where('id_vehiculo', $vehiculo)->lockForUpdate()->findOrFail($recepcion);

            if (! $recepcion->esDeHoy()) {
                abort(403, 'Solo se puede modificar una recepción el mismo día en que se registró.');
            }

            $this->validarKilometraje($recepcion, (int) $data['kilometraje']);

            // Guardar qué cambió, con su valor anterior y el nuevo
            $etiquetas = [
                'kilometraje' => 'Kilometraje',
                'nivel_combustible' => 'Combustible',
                'danos_visibles' => 'Daños visibles',
                'observaciones' => 'Observaciones',
            ];
            $cambios = [];
            foreach ($etiquetas as $campo => $etiqueta) {
                $antes = $recepcion->{$campo};
                $despues = $data[$campo] ?? null;
                if ((string) $antes !== (string) $despues) {
                    $cambios[] = "{$etiqueta}: \"" . ($antes ?? 'vacío') . '" → "' . ($despues ?? 'vacío') . '"';
                }
            }

            if (empty($cambios)) {
                throw ValidationException::withMessages([
                    'kilometraje' => 'No hiciste ningún cambio en la recepción.',
                ]);
            }

            $recepcion->update([
                'kilometraje' => $data['kilometraje'],
                'nivel_combustible' => $data['nivel_combustible'],
                'danos_visibles' => $data['danos_visibles'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'editado_at' => now(),
                'id_usuario_edicion' => $request->user()->id_usuario,
                'motivo_edicion' => $data['motivo'],
            ]);

            $placa = Vehiculo::withTrashed()->find($vehiculo)?->placa;
            AuditLog::registrar(
                'editar',
                'recepciones',
                "Se modificó la recepción #{$recepcion->id_recepcion} del vehículo {$placa}. "
                . implode('; ', $cambios) . ". Motivo: {$data['motivo']}"
            );

            $recepcion->load(['usuario:id_usuario,username', 'editor:id_usuario,username']);
            $recepcion->setAttribute('puede_editar', $recepcion->puedeEditar($request->user()));

            return response()->json($recepcion);
        });
    }

    /**
     * El kilometraje corregido debe quedar entre el de la recepción anterior y el de la siguiente.
     */
    private function validarKilometraje(Recepcion $recepcion, int $kilometraje): void
    {
        $otras = Recepcion::where('id_vehiculo', $recepcion->id_vehiculo)
            ->where('id_recepcion', '!=', $recepcion->id_recepcion);

        $anterior = (clone $otras)
            ->where(fn ($q) => $q->where('fecha_recepcion', '<', $recepcion->fecha_recepcion)
                ->orWhere(fn ($q) => $q->where('fecha_recepcion', $recepcion->fecha_recepcion)
                    ->where('id_recepcion', '<', $recepcion->id_recepcion)))
            ->max('kilometraje');

        $siguiente = (clone $otras)
            ->where(fn ($q) => $q->where('fecha_recepcion', '>', $recepcion->fecha_recepcion)
                ->orWhere(fn ($q) => $q->where('fecha_recepcion', $recepcion->fecha_recepcion)
                    ->where('id_recepcion', '>', $recepcion->id_recepcion)))
            ->min('kilometraje');

        if ($anterior !== null && $kilometraje < $anterior) {
            throw ValidationException::withMessages([
                'kilometraje' => "El kilometraje no puede ser menor al de la recepción anterior ({$anterior} km).",
            ]);
        }

        if ($siguiente !== null && $kilometraje > $siguiente) {
            throw ValidationException::withMessages([
                'kilometraje' => "El kilometraje no puede ser mayor al de una recepción posterior ({$siguiente} km).",
            ]);
        }
    }
}