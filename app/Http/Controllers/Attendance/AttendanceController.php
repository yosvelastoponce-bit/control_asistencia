<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\QrCode;
use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function scan(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || !$user->belongsToEnabledSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'El acceso de tu colegio esta bloqueado por el super admin.',
            ], 403);
        }

        $request->validate([
            'uuid'        => 'required|string',
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        // \Log::info('=== SCAN QR ===');
        // \Log::info('UUID recibido:', ['uuid' => $request->uuid]);
        // \Log::info('Schedule ID:', ['schedule_id' => $request->schedule_id]);
        // 1. Buscar el QR por uuid
        $qrCode = QrCode::where('uuid', $request->uuid)
                        ->where('active', true)
                        ->first();

        if (!$qrCode) {
            return response()->json([
                'success' => false,
                'message' => '❌ Código QR inválido o inactivo.',
            ], 404);
        }

        // 2. Buscar el estudiante
        $student = Student::find($qrCode->student_id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => '❌ Estudiante no encontrado.',
            ], 404);
        }

        // 3. Buscar el horario
        $schedule = Schedule::find($request->schedule_id);

        // 4. Verificar que el estudiante pertenece al grado y sección del horario
        if ($student->grade_id !== $schedule->grade_id ||
            $student->section_id !== $schedule->section_id) {
            return response()->json([
                'success' => false,
                'message' => "⛔ {$student->name} no pertenece a este horario.",
                'student' => $student->name,
            ], 403);
        }

        $today = Carbon::today()->toDateString();
        $now   = Carbon::now()->toTimeString();

        // 5. Verificar si ya tiene asistencia hoy para este horario
        $yaRegistrado = Attendance::where('student_id',  $student->id)
                                  ->where('schedule_id', $request->schedule_id)
                                  ->where('date',        $today)
                                  ->first();

        if ($yaRegistrado) {
            return response()->json([
                'success' => false,
                'message' => "⚠️ {$student->name} ya tiene asistencia registrada hoy.",
                'student' => $student->name,
            ], 200);
        }

        // 6. Determinar si es tarde comparando con start_time del horario
        $horaInicio  = Carbon::parse($schedule->start_time);
        $horaActual  = Carbon::parse($now);
        $minutesTarde = 15; // margen de tolerancia
        $status = $horaActual->gt($horaInicio->addMinutes($minutesTarde)) ? 'late' : 'present';

        // 7. Registrar asistencia
        Attendance::create([
            'student_id'    => $student->id,
            'schedule_id'   => $request->schedule_id,
            'qr_code_id'    => $qrCode->id,
            'registered_by' => $user->id,
            'date'          => $today,
            'time'          => $now,
            'status'        => $status,
        ]);

        $statusMsg = $status === 'late' ? '🕐 Tarde' : '✅ A tiempo';

        return response()->json([
            'success' => true,
            'message' => "✅ {$student->name} - {$statusMsg}",
            'student' => $student->name,
            'status'  => $status,
        ]);
    }
}
