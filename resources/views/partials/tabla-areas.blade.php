<div class="table-responsive">
    <table class="table table-sm align-middle">
        <caption class="visually-hidden">Puntaje por área de competencia</caption>
        <thead><tr><th scope="col">Área</th><th scope="col" class="text-end">Puntaje (0-100)</th><th scope="col">Nivel</th>@isset($comparacion)<th scope="col" class="text-end">Referencia</th>@endisset</tr></thead>
        <tbody>
        @foreach($perfil->areas_evaluadas_json as $area)
            <tr>
                <th scope="row">{{ $area['nombre'] }}</th>
                <td class="text-end">{{ number_format($area['puntaje'], 2) }}</td>
                <td><span class="badge text-bg-{{ ['basico' => 'warning', 'intermedio' => 'info', 'avanzado' => 'success'][$area['nivel']] ?? 'secondary' }}">{{ \App\Enums\NivelCompetencia::from($area['nivel'])->etiqueta() }}</span></td>
                @isset($comparacion)
                    <td class="text-end">{{ isset($comparacion->puntajesPorCodigo()[$area['codigo']]) ? number_format($comparacion->puntajesPorCodigo()[$area['codigo']], 2) : '—' }}</td>
                @endisset
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
