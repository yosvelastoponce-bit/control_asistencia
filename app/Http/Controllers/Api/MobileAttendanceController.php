<?php
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\GeneralAttendance;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
 
class MobileAttendanceController extends Controller
{
    // Registro individual
    public function store(Request $request)
    {
        $request->validate([
            'uuid'   => 'required|string',
            'date'   => 'required|date',
            'time'   => 'required',
            'status' => 'required|in:on_time,late',
        ]);
 
        $qrCode = QrCode::where('uuid', $request->uuid)
            ->where('active', true)
            ->with('student.school')
            ->first();
 
        if (!$qrCode) {
            return response()->json(['message' => 'QR no válido.'], 404);
        }
 
        $student = $qrCode->student;
 
        // Verificar que pertenece al mismo colegio
        if ($student->school_id !== $request->user()->school_id) {
            return response()->json(['message' => 'Sin permiso.'], 403);
        }
 
        // Verificar duplicado
        $exists = GeneralAttendance::where('student_id', $student->id)
            ->where('date', $request->date)
            ->exists();
 
        if ($exists) {
            return response()->json(['message' => 'Ya registrado.'], 409);
        }
 
        GeneralAttendance::create([
            'student_id' => $student->id,
            'school_id'  => $student->school_id,
            'qr_code_id' => $qrCode->id,
            'date'       => $request->date,
            'time'       => $request->time,
            'status'     => $request->status,
        ]);
 
        // Opcional: sync Google Sheets
        // ...
 
        return response()->json(['success' => true]);
    }
 
    // Envío masivo (bulk sync)
    public function bulkStore(Request $request)
    {
        $records = $request->input('records', []);
        $ok      = 0;
 
        foreach ($records as $record) {
            try {
                $this->store(new Request($record));
                $ok++;
            } catch (\Exception $e) {
                // Continuar con el siguiente
            }
        }
 
        return response()->json(['synced' => $ok, 'total' => count($records)]);
    }
}