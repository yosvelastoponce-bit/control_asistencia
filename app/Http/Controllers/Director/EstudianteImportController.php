<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EstudianteImportController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'archivo.required' => 'Debes subir un archivo.',
            'archivo.mimes'    => 'Solo se aceptan archivos .csv',
            'archivo.max'      => 'El archivo no debe superar 5MB.',
        ]);

        $schoolId = $this->schoolId();

        // Leer todo el contenido y convertir encoding si es necesario
        $contenido = file_get_contents($request->file('archivo')->getRealPath());

        // Quitar BOM UTF-8
        $contenido = str_replace("\xEF\xBB\xBF", '', $contenido);

        // Detectar si es Windows-1252 y convertir a UTF-8
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        // Guardar en temporal ya limpio
        $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpPath, $contenido);

        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return response()->json(['message' => 'No se pudo procesar el archivo.'], 422);
        }

        // Detectar separador (coma o punto y coma)
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        // Leer encabezados
        $rawHeaders = fgetcsv($handle, 0, $separator);
        if (!$rawHeaders) {
            fclose($handle);
            @unlink($tmpPath);
            return response()->json(['message' => 'El archivo está vacío.'], 422);
        }

        // Normalizar encabezados
        $headers = array_map(fn($h) => $this->normalizar((string) $h), $rawHeaders);

        // Mapear columnas por nombre; si falla, usar posición fija
        $colIdx = $this->mapearColumnas($headers);

        if ($colIdx === null) {
            fclose($handle);
            @unlink($tmpPath);
            return response()->json([
                'message' => 'No se reconocieron las columnas. Encabezados detectados: "' . implode('", "', $rawHeaders) . '". Deben ser: nombre, dni, contraseña, grado, sección'
            ], 422);
        }

        $imported = 0;
        $errors   = [];
        $students = [];
        $lineNum  = 1;

        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            $lineNum++;

            $nombre     = trim((string) ($row[$colIdx['nombre']]     ?? ''));
            $dni        = trim((string) ($row[$colIdx['dni']]        ?? ''));
            $password   = trim((string) ($row[$colIdx['contrasena']] ?? ''));
            $gradoNom   = trim((string) ($row[$colIdx['grado']]      ?? ''));
            $seccionNom = trim((string) ($row[$colIdx['seccion']]    ?? ''));

            // Saltar filas vacías
            if (!$nombre && !$dni) continue;

            // Validar campos
            $rowErrors = [];
            if (!$nombre)     $rowErrors[] = "Fila $lineNum: nombre vacío.";
            if (!$dni)        $rowErrors[] = "Fila $lineNum: DNI vacío.";
            if (!$password)   $rowErrors[] = "Fila $lineNum: contraseña vacía.";
            if (!$gradoNom)   $rowErrors[] = "Fila $lineNum: grado vacío.";
            if (!$seccionNom) $rowErrors[] = "Fila $lineNum: sección vacía.";

            if ($rowErrors) {
                $errors = array_merge($errors, $rowErrors);
                continue;
            }

            // DNI duplicado
            if (Student::where('dni', $dni)->exists()) {
                $errors[] = "Fila $lineNum: DNI \"$dni\" ya está registrado, se omitió.";
                continue;
            }

            $gradoFinal   = $this->normalizarGrado($gradoNom);
            $seccionFinal = ucfirst(strtolower($seccionNom));

            try {
                $student = DB::transaction(function () use (
                    $nombre, $dni, $password, $gradoFinal, $seccionFinal, $schoolId
                ) {
                    $grade = Grade::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => $gradoFinal]
                    );

                    $section = Section::firstOrCreate(
                        ['grade_id' => $grade->id, 'name' => $seccionFinal]
                    );

                    $user = AppUser::create([
                        'school_id' => $schoolId,
                        'name'      => $nombre,
                        'email'     => null,
                        'password'  => Hash::make($password),
                        'role'      => 'student',
                    ]);

                    $student = Student::create([
                        'user_id'    => $user->id,
                        'school_id'  => $schoolId,
                        'name'       => $nombre,
                        'dni'        => $dni,
                        'grade_id'   => $grade->id,
                        'section_id' => $section->id,
                    ]);

                    $student->load(['grade', 'section', 'qrCode']);
                    return $student;
                });

                $students[] = $student;
                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Fila $lineNum: Error al guardar \"$nombre\" — " . $e->getMessage();
            }
        }

        fclose($handle);
        @unlink($tmpPath);

        return response()->json([
            'imported' => $imported,
            'errors'   => $errors,
            'students' => $students,
        ]);
    }

    /**
     * Intenta mapear columnas por nombre normalizado.
     * Si no encuentra alguna, intenta por posición fija (0,1,2,3,4).
     * Retorna null si hay menos de 5 columnas.
     */
    private function mapearColumnas(array $headers): ?array
    {
        // Alias aceptados para cada columna
        $alias = [
            'nombre'     => ['nombre', 'name', 'alumno', 'estudiante'],
            'dni'        => ['dni', 'documento', 'cedula', 'id'],
            'contrasena' => ['contrasena', 'password', 'clave', 'pass', 'contraseña'],
            'grado'      => ['grado', 'grade', 'nivel', 'año'],
            'seccion'    => ['seccion', 'section', 'sección', 'aula', 'grupo'],
        ];

        $resultado = [];

        foreach ($alias as $campo => $posibles) {
            $encontrado = false;
            foreach ($headers as $idx => $header) {
                if (in_array($header, $posibles)) {
                    $resultado[$campo] = $idx;
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                // No encontró por nombre — intentar por posición fija
                $posicionFija = ['nombre' => 0, 'dni' => 1, 'contrasena' => 2, 'grado' => 3, 'seccion' => 4];
                if (count($headers) >= 5) {
                    $resultado[$campo] = $posicionFija[$campo];
                } else {
                    return null;
                }
            }
        }

        return $resultado;
    }

    private function normalizar(string $value): string
    {
        // Quitar BOM y espacios/comillas
        $value = str_replace("\xEF\xBB\xBF", '', $value);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $value = strtolower($value);

        // Reemplazar byte a byte los caracteres latinos comunes
        // Cubre tanto UTF-8 multibyte como posibles restos de Latin-1
        $buscar = [
            // UTF-8
            'á','à','ä','â','é','è','ë','ê','í','ì','ï','î',
            'ó','ò','ö','ô','ú','ù','ü','û','ñ','ç',
            // Mayúsculas por si strtolower no las cubrió
            'Á','À','É','È','Í','Ì','Ó','Ò','Ú','Ù','Ñ',
        ];
        $reemplazar = [
            'a','a','a','a','e','e','e','e','i','i','i','i',
            'o','o','o','o','u','u','u','u','n','c',
            'a','a','e','e','i','i','o','o','u','u','n',
        ];

        return str_replace($buscar, $reemplazar, $value);
    }

    private function normalizarGrado(string $grado): string
    {
        $grado = mb_strtolower(trim($grado));
        $grado = preg_replace('/\s*(grado|grade)\s*/i', '', $grado);
        $grado = trim($grado);

        $mapa = [
            '1' => '1er Grado', '1ro' => '1er Grado', '1er' => '1er Grado',
            '1°' => '1er Grado', 'primero' => '1er Grado',
            '2' => '2do Grado', '2do' => '2do Grado', '2°' => '2do Grado',
            'segundo' => '2do Grado',
            '3' => '3er Grado', '3ro' => '3er Grado', '3er' => '3er Grado',
            '3°' => '3er Grado', 'tercero' => '3er Grado',
            '4' => '4to Grado', '4to' => '4to Grado', '4°' => '4to Grado',
            'cuarto' => '4to Grado',
            '5' => '5to Grado', '5to' => '5to Grado', '5°' => '5to Grado',
            'quinto' => '5to Grado',
        ];

        return $mapa[$grado] ?? (ucfirst($grado) . ' Grado');
    }
}