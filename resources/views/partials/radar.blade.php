{{-- Gráfico de radar generado en el servidor (SVG accesible) con tabla equivalente para lectores de pantalla. --}}
<figure class="text-center mb-0">
    <div class="d-inline-block mw-100">{!! $grafico !!}</div>
    @isset($leyenda)
        <figcaption class="small text-muted mt-2">{{ $leyenda }}</figcaption>
    @endisset
</figure>
