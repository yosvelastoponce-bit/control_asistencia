<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .grid {
            display: table;
            width: 100%;
        }

        .row {
            display: table-row;
        }

        /* 3 columnas por fila */
        .cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px 10px;
            vertical-align: top;
            border: 1px dashed #ccc; /* línea de corte */
        }

        .qr-img {
            width: 150px;
            height: 150px;
        }

        .student-name {
            margin-top: 8px;
            font-size: 13px;
            font-weight: bold;
            color: #222;
        }

        .student-uuid {
            margin-top: 4px;
            font-size: 8px;
            color: #888;
            word-break: break-all;
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
                        <img
                            class="qr-img"
                            src="data:image/svg+xml;base64,{{ $student['qr_svg'] }}"
                            alt="QR {{ $student['name'] }}"
                        />
                        <div class="student-name">{{ $student['name'] }}</div>
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