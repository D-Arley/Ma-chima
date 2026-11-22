<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de movimientos Ma'chima</title>
    <style>
        body { font-family: Arial, sans-serif; color: #171615; margin: 32px; }
        header { border-bottom: 3px solid #c99c42; padding-bottom: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 24px; }
        .period { color: #716d65; font-size: 12px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #171511; color: #fff; text-align: left; padding: 8px; }
        td { border-bottom: 1px solid #ddd4c3; padding: 8px; }
        .right { text-align: right; }
        @media print { button { display: none } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="float:right;padding:10px 16px">Guardar como PDF / Imprimir</button>
    <header>
        <h1>Ma'chima · Informe de movimientos</h1>
        <p class="period">
            {{ $from ? 'Desde '.$from->format('d/m/Y') : 'Desde el primer registro' }}
            ·
            {{ $to ? 'Hasta '.$to->format('d/m/Y') : 'Hasta hoy' }}
        </p>
    </header>
    <table>
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Referencia</th><th>Usuario</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @foreach ($movimientos as $movimiento)
                <tr>
                    <td>{{ $movimiento['fecha']->format('d/m/Y H:i') }}</td>
                    <td>{{ $movimiento['tipo'] }}</td>
                    <td>{{ $movimiento['detalle'] }}</td>
                    <td>{{ $movimiento['referencia'] }}</td>
                    <td>{{ $movimiento['usuario'] }}</td>
                    <td class="right">${{ number_format($movimiento['valor'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
