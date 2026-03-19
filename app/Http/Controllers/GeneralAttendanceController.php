<?php

namespace App\Http\Controllers;

use App\Models\GeneralAttendance;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GeneralAttendanceController extends Controller
{
    private const HORA_LIMITE = '08:00:00';

    public function index()
    {
        return Inertia::render('Welcome', [
            'scanner_mode' => 'general_entry',
        ]);
    }

    public function scan(Request $request)
    {
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
        $now     = Carbon::now();
        $today   = $now->toDateString();
        $timeNow = $now->format('H:i:s');

        // 2. Verificar si ya registró hoy
        $yaRegistrado = GeneralAttendance::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if ($yaRegistrado) {
            return response()->json([
                'success' => false,
                'message' => "⚠️ {$student->name} ya registró entrada hoy a las {$yaRegistrado->time}.",
                'student' => $student->name,
                'status'  => $yaRegistrado->status,
            ]);
        }

        // 3. Determinar estado
        $status = $timeNow <= self::HORA_LIMITE ? 'on_time' : 'late';

        // 4. Guardar en base de datos
        GeneralAttendance::create([
            'student_id' => $student->id,
            'school_id'  => $student->school_id,
            'qr_code_id' => $qrCode->id,
            'date'       => $today,
            'time'       => $timeNow,
            'status'     => $status,
        ]);

        // 5. Sincronizar con Google Sheets (no bloquea si falla)
        try {
            $this->appendToGoogleSheets([
                $today,
                $now->format('H:i'),
                $student->name,
                $student->dni ?? '—',
                $student->grade?->name  ?? '—',
                $student->section?->name ?? '—',
                $student->school?->name  ?? '—',
                $status === 'on_time' ? 'A tiempo' : 'Tardanza',
            ]);
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

    private function appendToGoogleSheets(array $row): void
    {
        $credentialsPath = storage_path('app/google-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Credenciales no encontradas en storage/app/google-credentials.json');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);

        $service = new Sheets($client);

        $spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $range         = config('services.google_sheets.range', 'Asistencia!A:H');

        $body = new ValueRange(['values' => [$row]]);

        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }
}