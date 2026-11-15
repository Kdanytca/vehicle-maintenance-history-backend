<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte por placa {{ $vehiculo->placa }}</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 20px; color: #075985; }
        .meta { margin-bottom: 18px; }
        .archivado { padding: 8px; background: #fef3c7; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        th { background: #e2e8f0; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .total { text-align: right; margin-top: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Historial de mantenimientos</h1>
    <div class="meta">
        <p><strong>Placa / filtro aplicado:</strong> {{ $vehiculo->placa }}</p>
        <p><strong>Vehículo:</strong> {{ $vehiculo->marca }} {{ $vehiculo->modelo }}</p>
        <p><strong>Propietario:</strong> {{ $vehiculo->propietario?->nombre ?? 'Sin propietario' }}</p>
        <p><strong>Fecha de generación:</strong> {{ $generadoEn->format('Y-m-d H:i:s') }} ({{ config('app.timezone') }})</p>
    </div>
    @if ($vehiculo->deleted_at)
        <p class="archivado">Vehículo archivado. Se conserva su historial de mantenimientos.</p>
    @endif
    <table>
        <thead><tr>
            <th style="width: 12%">Fecha del mantenimiento</th>
            <th style="width: 36%">Mantenimiento / descripción</th>
            <th style="width: 13%">Estado</th>
            <th style="width: 23%">Mecánico responsable</th>
            <th style="width: 16%">Mano de obra</th>
        </tr></thead>
        <tbody>
        @foreach ($mantenimientos as $item)
            <tr>
                <td>{{ $item->fecha_servicio }}</td>
                <td>{{ $item->descripcion_falla }}</td>
                <td>{{ $item->estado }}</td>
                <td>{{ $item->encargado?->username ?? 'Sin responsable asignado' }}</td>
                <td>${{ number_format($item->costo_mano_obra ?? 0, 2, '.', ',') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p>{{ $mantenimientos->count() }} mantenimiento(s)</p>
    <p class="total">Total de mano de obra: ${{ number_format($mantenimientos->sum('costo_mano_obra'), 2, '.', ',') }}</p>
</body>
</html>
