<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\GeneralAttendance;
use App\Models\QrCode;
use App\Models\School;
use App\Models\Student;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MobileController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:director,teacher'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = AppUser::with('school')
            ->where('email', $data['email'])
            ->where('role', $data['role'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if (!$user->canTakeGeneralAttendance()) {
            return response()->json([
                'message' => 'No tienes permisos para registrar asistencia.',
            ], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'presenteya-android')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'school' => $this->schoolPayload($user->school),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();
        $school = $user->school;

        $students = Student::query()
            ->where('school_id', $user->school_id)
            ->with(['grade:id,name', 'section:id,name', 'qrCode:id,student_id,uuid,active'])
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'dni' => $student->dni,
                    'grade_name' => $student->grade?->name ?? '',
                    'section_name' => $student->section?->name ?? '',
                    'qr_uuid' => $student->qrCode?->uuid ?? '',
                    'school_id' => $student->school_id,
                ];
            })
            ->filter(fn (array $student) => $student['qr_uuid'] !== '')
            ->values();

        return response()->json([
            'user' => $this->userPayload($user),
            'school' => $this->schoolPayload($school),
            'students' => $students,
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();

        if (!$user->canTakeGeneralAttendance()) {
            return response()->json([
                'message' => 'No tienes permisos para registrar asistencia.',
            ], 403);
        }

        $data = $request->validate([
            'uuid' => ['required', 'string'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i:s'],
            'status' => ['required', 'in:on_time,late'],
            'school_id' => ['nullable', 'integer'],
        ]);

        $qrCode = QrCode::query()
            ->where('uuid', $data['uuid'])
            ->where('active', true)
            ->with('student.grade', 'student.section', 'student.school')
            ->first();

        if (!$qrCode || !$qrCode->student) {
            return response()->json([
                'message' => 'Codigo QR no valido o inactivo.',
            ], 404);
        }

        $student = $qrCode->student;
        $school = $student->school;

        if ($student->school_id !== $user->school_id) {
            return response()->json([
                'message' => 'El estudiante no pertenece a tu colegio.',
            ], 403);
        }

        $existing = GeneralAttendance::query()
            ->where('student_id', $student->id)
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'La asistencia ya estaba registrada.',
                'status' => 'already_registered',
            ]);
        }

        $attendance = GeneralAttendance::create([
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'qr_code_id' => $qrCode->id,
            'registered_by' => $user->id,
            'date' => $data['date'],
            'time' => $data['time'],
            'status' => $data['status'],
        ]);

        try {
            if ($school->google_sheet_id) {
                $this->appendToGoogleSheets($school->google_sheet_id, [[
                    $data['date'],
                    substr($data['time'], 0, 5),
                    $student->name,
                    $student->dni ?? '-',
                    $student->grade?->name ?? '-',
                    $student->section?->name ?? '-',
                    $data['status'] === 'on_time' ? 'Presente' : 'Tarde',
                ]]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Mobile Google Sheets sync failed: '.$exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Asistencia sincronizada correctamente.',
            'attendance_id' => $attendance->id,
        ], 201);
    }

    private function userPayload(AppUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'school_id' => $user->school_id,
            'school_name' => $user->school?->name ?? '',
            'can_take_general_attendance' => $user->canTakeGeneralAttendance(),
        ];
    }

    private function schoolPayload(?School $school): array
    {
        if (!$school) {
            return [];
        }

        return [
            'id' => $school->id,
            'name' => $school->name,
            'entry_start' => $school->entry_start ?? '07:00:00',
            'entry_limit' => $school->entry_limit ?? '08:00:00',
            'entry_end' => $school->entry_end ?? '09:00:00',
        ];
    }

    private function appendToGoogleSheets(string $spreadsheetId, array $rows): void
    {
        $credentialsPath = storage_path('app/google-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException('Credenciales de Google no encontradas.');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);

        $service = new Sheets($client);
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheetTitle = $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        $range = $sheetTitle.'!A1:G1';

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
