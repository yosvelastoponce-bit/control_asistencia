<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; }

        .grid { display: table; width: 100%; border-collapse: collapse; }
        .row  { display: table-row; }

        /* 3 columnas por fila */
        .cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 12px 8px;
            vertical-align: top;
            border: 1px dashed #ccc;
        }

        /* Cabecera del colegio */
        .school-header {
            margin-bottom: 6px;
            text-align: center;
        }
        .school-logo-small {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        .school-name {
            font-size: 8px;
            font-weight: bold;
            color: #333;
            margin-top: 2px;
        }
        .school-code {
            font-size: 7px;
            color: #888;
        }

        /* QR wrapper usando tabla para centrar el logo */
        .qr-container {
            width: 140px;
            margin: 0 auto;
            position: relative;
        }
        .qr-img {
            width: 140px;
            height: 140px;
        }

        /* Logo centrado sobre el QR usando tabla de posicionamiento */
        .logo-overlay-table {
            width: 140px;
            height: 140px;
            margin-top: -140px; /* sube sobre el QR */
        }
        .logo-overlay-table td {
            text-align: center;
            vertical-align: middle;
            padding-top: 8px; /* Agregado: baja el icono verticalmente */
        }
        .logo-center {
            width: 34px;
            height: 34px;
            object-fit: contain;
            background: #fff;
            border: 2px solid #fff;
            border-radius: 4px;
            margin-top: 43px; /* Agregado: desplaza el icono hacia abajo */
        }
        .logo-center-placeholder {
            width: 34px;
            height: 34px;
            background: #1a283f;
            color: #fff;
            font-size: 25px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 2px solid #fff;
            border-radius: 4px;
            line-height: 30px;
            display: inline-block;
            margin-top: 43px; /* Agregado: desplaza el placeholder hacia abajo */
        }

        .student-name {
            margin-top: 6px;
            font-size: 11px;
            font-weight: bold;
            color: #222;
        }
        .student-grade-section {
            margin-top: 2px;
            font-size: 9px;
            font-weight: bold;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="grid">
        @php $chunks = $students->chunk(3); @endphp

        @foreach ($chunks as $row)
            <div class="row">
                @foreach ($row as $student)
                    <div class="cell">

                        {{-- Cabecera: logo pequeño + nombre + código --}}
                        <div class="school-header">
                            @if(!empty($student['school_logo']))
                                <img class="school-logo-small"
                                    src="{{ $student['school_logo'] }}"
                                    alt="Logo"/>
                            @endif
                            <div class="school-name">{{ $student['school_name'] ?? '' }}</div>
                            <div class="school-code">Cód: {{ $student['school_code'] ?? '' }}</div>
                        </div>

                        <div class="qr-container">
                            {{-- 1. QR --}}
                            <img class="qr-img"
                                src="data:image/svg+xml;base64,{{ $student['qr_svg'] }}"
                                alt="QR"/>

                            {{-- 2. Logo encima usando tabla con margin negativo --}}
                            @if(!empty($app_logo))
                                <table class="logo-overlay-table" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>
                                            <img class="logo-center"
                                                src="{{ $app_logo }}"
                                                alt="Logo"/>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </div>

                        {{-- Nombre del estudiante --}}
                        <div class="student-name">{{ $student['name'] }}</div>
                        @if (!empty($student['grade_section']))
                            <div class="student-grade-section">{{ $student['grade_section'] }}</div>
                        @endif

                    </div>
                @endforeach

                {{-- Rellenar celdas vacías si la fila tiene menos de 3 --}}
                @for ($i = count($row); $i < 3; $i++)
                    <div class="cell"></div>
                @endfor
            </div>
        @endforeach
    </div>
</body>
</html>
