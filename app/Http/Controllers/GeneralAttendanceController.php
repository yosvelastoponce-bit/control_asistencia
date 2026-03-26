<?php

namespace App\Http\Controllers;

use App\Models\GeneralAttendance;
use App\Models\QrCode;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GeneralAttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::guard('app_user')->user();

        if (!$user) {
            return redirect()->route('home');
        }

        if (!$user->canTakeGeneralAttendance()) {
            abort(403, 'No tienes permisos para registrar asistencia general.');
        }

        return Inertia::render('GeneralAttendance/Index', [
            'auth' => [
                'user' => $user,
            ],
        ]);
    }

    public function publicHome()
    {
        return Inertia::render('Home', [
            'auth' => [
                'user' => Auth::guard('app_user')->user(),
            ],
        ]);
    }

    public function scan(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || !$user->canTakeGeneralAttendance()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para registrar asistencia general.',
                'status'  => 'unauthorized',
            ], 403);
        }

        $request->validate([
            'uuid' => 'required|string',
        ]);

        // 1. Buscar el QR
        $qrCode = QrCode::where('uuid', $request->uuid)
            ->where('active', true)
            ->with('student.grade', 'student.section', 'student.school')
            ->first();

        if (!$qrCode) {
            return response()->json([
                'success' => false,
                'message' => '❌ Código QR no válido o inactivo.',
            ], 404);
        }

        $student = $qrCode->student;
        $school  = $student->school;
        $now     = Carbon::now();
        $today   = $now->toDateString();
        $timeNow = $now->format('H:i:s');

        // Obtener horarios configurados del colegio (con valores por defecto)
        $entryStart = $school->entry_start ?? '07:00:00';
        $entryLimit = $school->entry_limit ?? '08:00:00';
        $entryEnd   = $school->entry_end   ?? '09:00:00';

        // Verificar si está dentro del horario de registro
        if ($timeNow < $entryStart) {
            return response()->json([
                'success' => false,
                'message' => "⏰ El registro de entrada aún no ha comenzado. Inicia a las " . substr($entryStart, 0, 5) . ".",
                'student' => $student->name,
                'status'  => 'too_early',
            ]);
        }

        if ($timeNow > $entryEnd) {
            return response()->json([
                'success' => false,
                'message' => "🚫 El tiempo de registro ya cerró a las " . substr($entryEnd, 0, 5) . ".",
                'student' => $student->name,
                'status'  => 'too_late',
            ]);
        }

        // Verificar si ya registró hoy
        $yaRegistrado = GeneralAttendance::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if ($yaRegistrado) {
            return response()->json([
                'success' => false,
                'message' => "⚠️ {$student->name} ya registró entrada hoy a las {$yaRegistrado->time}.",
                'student' => $student->name,
                'status'  => 'already_registered',
            ]);
        }

        // Determinar si es puntual o tardanza
        $status = $timeNow <= $entryLimit ? 'on_time' : 'late';

        GeneralAttendance::create([
            'student_id' => $student->id,
            'school_id'  => $student->school_id,
            'qr_code_id' => $qrCode->id,
            'registered_by' => $user->id,
            'date'       => $today,
            'time'       => $timeNow,
            'status'     => $status,
        ]);

        // Sincronizar con Google Sheets
        try {
            $sheetId = $school->google_sheet_id;
            if ($sheetId) {
                $this->appendToGoogleSheets($sheetId, [[
                    $today,
                    $now->format('H:i'),
                    $student->name,
                    $student->dni ?? '—',
                    $student->grade?->name   ?? '—',
                    $student->section?->name ?? '—',
                    $status === 'on_time' ? 'Presente' : 'Tarde',
                ]]);
            }
        } catch (\Exception $e) {
            \Log::warning('Google Sheets sync failed: ' . $e->getMessage());
        }

        $mensaje = $status === 'on_time'
            ? "✅ {$student->name} — Entrada registrada a tiempo."
            : "🕐 {$student->name} — Entrada registrada con tardanza.";

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'student' => $student->name,
            'status'  => $status,
            'time'    => $now->format('H:i:s'),
        ]);
    }

    // private function appendToGoogleSheets(string $spreadsheetId, array $row): void
    // {
    //     $credentialsPath = storage_path('app/google-credentials.json');

    //     if (!file_exists($credentialsPath)) {
    //         throw new \Exception('Credenciales no encontradas.');
    //     }

    //     $client = new GoogleClient();
    //     $client->setAuthConfig($credentialsPath);
    //     $client->addScope(Sheets::SPREADSHEETS);

    //     $service = new Sheets($client);

    //     $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    //     $sheetTitle  = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
    //     $range       = $sheetTitle . '!A:G';

    //     $body = new ValueRange(['values' => [$row]]);

    //     $service->spreadsheets_values->append(
    //         $spreadsheetId,
    //         $range,
    //         $body,
    //         ['valueInputOption' => 'USER_ENTERED']
    //     );
    // }

    /**
     * Procesar ausencias al finalizar el horario de entrada
     * Este método debe ser ejecutado por un cron job o manualmente
     */
    // public function processAbsences(Request $request)
    // {
    //     $schoolId = $request->input('school_id');
    //     $date = $request->input('date', Carbon::now()->toDateString());
        
    //     // Obtener la escuela
    //     $school = \App\Models\School::findOrFail($schoolId);
        
    //     // Obtener horario de entrada
    //     $entryEnd = $school->entry_end ?? '09:00:00';
        
    //     // Verificar si ya pasó el horario de cierre
    //     $now = Carbon::now();
    //     $closingTime = Carbon::parse($date . ' ' . $entryEnd);
        
    //     if ($now->lt($closingTime) && !$request->input('force', false)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "El horario de entrada aún no ha cerrado. Cierra a las " . substr($entryEnd, 0, 5),
    //         ]);
    //     }
        
    //     // Obtener todos los estudiantes activos de la escuela
    //     $allStudents = Student::where('school_id', $schoolId)
    //         ->where('active', true)
    //         ->get();
        
    //     // Obtener estudiantes que ya registraron asistencia hoy
    //     $registeredStudents = GeneralAttendance::where('school_id', $schoolId)
    //         ->where('date', $date)
    //         ->pluck('student_id')
    //         ->toArray();
        
    //     // Encontrar estudiantes que no registraron (ausentes)
    //     $absentStudents = $allStudents->filter(function ($student) use ($registeredStudents) {
    //         return !in_array($student->id, $registeredStudents);
    //     });
        
    //     $absentCount = 0;
    //     $errors = [];
        
    //     // Procesar cada ausente
    //     foreach ($absentStudents as $student) {
    //         try {
    //             // Buscar un QR code del estudiante (puede ser cualquiera)
    //             $qrCode = QrCode::where('student_id', $student->id)
    //                 ->where('active', true)
    //                 ->first();
                
    //             if (!$qrCode) {
    //                 $errors[] = "No se encontró QR activo para estudiante ID: {$student->id}";
    //                 continue;
    //             }
                
    //             // Crear registro de ausencia
    //             GeneralAttendance::create([
    //                 'student_id' => $student->id,
    //                 'school_id' => $schoolId,
    //                 'qr_code_id' => $qrCode->id,
    //                 'date' => $date,
    //                 'time' => null, // Sin hora para ausencias
    //                 'status' => 'absent',
    //             ]);
                
    //             $absentCount++;
    //         } catch (\Exception $e) {
    //             $errors[] = "Error al registrar ausencia de {$student->name}: " . $e->getMessage();
    //             Log::error('Error processing absence: ' . $e->getMessage());
    //         }
    //     }
        
    //     // Sincronizar ausencias con Google Sheets
    //     $absentData = [];
    //     foreach ($absentStudents as $student) {
    //         $absentData[] = [
    //             $date,
    //             '—', // Sin hora
    //             $student->name,
    //             $student->dni ?? '—',
    //             $student->grade?->name ?? '—',
    //             $student->section?->name ?? '—',
    //             'Ausente',
    //         ];
    //     }
        
    //     if (!empty($absentData) && $school->google_sheet_id) {
    //         try {
    //             $this->appendToGoogleSheets($school->google_sheet_id, $absentData);
    //         } catch (\Exception $e) {
    //             Log::warning('Google Sheets sync failed for absences: ' . $e->getMessage());
    //             $errors[] = 'Error al sincronizar con Google Sheets';
    //         }
    //     }
        
    //     return response()->json([
    //         'success' => true,
    //         'message' => "Procesamiento completado. {$absentCount} ausentes registrados.",
    //         'absent_count' => $absentCount,
    //         'total_students' => $allStudents->count(),
    //         'registered_count' => count($registeredStudents),
    //         'errors' => $errors,
    //     ]);
    // }
    /**
     * Procesar ausentes: registrar en BD y Google Sheets los alumnos que no marcaron hoy
     */
    public function processAbsences(Request $request)
    {
        $schoolId = $request->input('school_id');
        $date     = $request->input('date', Carbon::now()->toDateString());
        $force    = $request->boolean('force', false);
 
        $school = \App\Models\School::findOrFail($schoolId);
 
        // Verificar si ya cerró el horario (salvo que se fuerce)
        if (!$force && !$school->isAttendanceClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'El horario de entrada aún no ha cerrado. Cierra a las ' . substr($school->entry_end, 0, 5),
            ], 400);
        }
 
        // Evitar doble procesamiento
        $yaProcessado = GeneralAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->where('status', 'absent')
            ->exists();
 
        if ($yaProcessado && !$force) {
            return response()->json([
                'success' => false,
                'message' => 'Las ausencias de hoy ya fueron procesadas.',
            ], 400);
        }
 
        // Todos los estudiantes del colegio
        $allStudents = Student::where('school_id', $schoolId)
            ->with(['grade', 'section', 'qrCode'])
            ->get();
 
        // Quienes YA registraron asistencia hoy (presentes o tardanza)
        $registrados = GeneralAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->whereIn('status', ['on_time', 'late'])
            ->pluck('student_id')
            ->toArray();
 
        // Ausentes = los que NO están en la lista de registrados
        $ausentes = $allStudents->filter(fn($s) => !in_array($s->id, $registrados));
 
        $absentCount = 0;
        $sheetRows   = [];
 
        foreach ($ausentes as $student) {
            $qrCode = $student->qrCode
                ?? QrCode::where('student_id', $student->id)->where('active', true)->first();
 
            if (!$qrCode) continue;
 
            // Evitar duplicado si ya existe registro absent para este estudiante hoy
            $existe = GeneralAttendance::where('student_id', $student->id)
                ->where('date', $date)
                ->exists();
 
            if (!$existe) {
                GeneralAttendance::create([
                    'student_id' => $student->id,
                    'school_id'  => $schoolId,
                    'qr_code_id' => $qrCode->id,
                    'date'       => $date,
                    'time'       => null,
                    'status'     => 'absent',
                ]);
                $absentCount++;
            }
 
            $sheetRows[] = [
                $date,
                '—',
                $student->name,
                $student->dni ?? '—',
                $student->grade?->name   ?? '—',
                $student->section?->name ?? '—',
                'Ausente',
            ];
        }
 
        // Sincronizar con Google Sheets
        if (!empty($sheetRows) && $school->google_sheet_id) {
            try {
                $this->appendToGoogleSheets($school->google_sheet_id, $sheetRows);
            } catch (\Exception $e) {
                Log::warning('Google Sheets absences sync failed: ' . $e->getMessage());
            }
        }
 
        return response()->json([
            'success'          => true,
            'message'          => "{$absentCount} ausente(s) registrados correctamente.",
            'absent_count'     => $absentCount,
            'total_students'   => $allStudents->count(),
            'registered_count' => count($registrados),
        ]);
    }

    private function appendToGoogleSheets(string $spreadsheetId, array $rows): void
    {
        $credentialsPath = storage_path('app/google-credentials.json');
 
        if (!file_exists($credentialsPath)) {
            throw new \Exception('Credenciales de Google no encontradas.');
        }
 
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
 
        $service     = new Sheets($client);
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheetTitle  = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        $range       = $sheetTitle . '!A1:G1';
 
        $body = new ValueRange(['values' => $rows]);
 
        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            [
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS',
            ]
        );
    }
    
}
