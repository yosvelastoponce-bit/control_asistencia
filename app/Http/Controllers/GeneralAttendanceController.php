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
            'date'       => $today,
            'time'       => $timeNow,
            'status'     => $status,
        ]);

        // Sincronizar con Google Sheets
        try {
            $sheetId = $school->google_sheet_id;
            if ($sheetId) {
                $this->appendToGoogleSheets($sheetId, [
                    $today,
                    $now->format('H:i'),
                    $student->name,
                    $student->dni ?? '—',
                    $student->grade?->name   ?? '—',
                    $student->section?->name ?? '—',
                    $status === 'on_time' ? 'A tiempo' : 'Tardanza',
                ]);
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

    private function appendToGoogleSheets(string $spreadsheetId, array $row): void
    {
        $credentialsPath = storage_path('app/google-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Credenciales no encontradas.');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);

        $service = new Sheets($client);

        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheetTitle  = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        $range       = $sheetTitle . '!A:G';

        $body = new ValueRange(['values' => [$row]]);

        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }
}