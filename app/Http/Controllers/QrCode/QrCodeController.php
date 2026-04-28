<?php

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\QrCode as QrCodeModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    public function generateQr()
    {
        $user = Auth::guard('app_user')->user();

        if (! $user) {
            return redirect()->route('director.login');
        }

        if (! in_array($user->role, ['director', 'teacher'], true)) {
            abort(403, 'No tienes permisos para descargar codigos QR.');
        }

        if (! $user->belongsToEnabledSchool()) {
            abort(403, 'El acceso de tu colegio esta bloqueado por el super admin.');
        }

        $schoolId = $user->school_id;
        $school   = \App\Models\School::find($schoolId);

        $students = $this->orderedStudentsQuery($schoolId)->get();

        // Crear QR si el estudiante no tiene uno
        foreach ($students as $student) {
            if (!$student->qrCode) {
                QrCodeModel::create([
                    'student_id' => $student->id,
                    'uuid'       => Str::uuid(),
                    'active'     => true,
                ]);
            }
        }

        // Logo del colegio en base64 (desde storage)
        $schoolLogo = null;
        if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
            $logoContent = Storage::disk('public')->get($school->logo_path);
            $extension   = pathinfo($school->logo_path, PATHINFO_EXTENSION);
            $mimeType    = $extension === 'png' ? 'image/png' : 'image/jpeg';
            $schoolLogo  = "data:{$mimeType};base64," . base64_encode($logoContent);
        }

        $students = $this->orderedStudentsQuery($schoolId)
            ->get()
            ->map(function ($student) use ($school, $schoolLogo) {
                $qrSvg = \QrCode::format('svg')
                    ->size(150)
                    ->errorCorrection('H')
                    ->generate($student->qrCode->uuid);

                $gradeSection = trim(implode(' ', array_filter([
                    $this->shortGradeName($student->grade?->name),
                    $student->section?->name,
                ])));

                return [
                    'name'        => $student->name,
                    'qr_svg'      => base64_encode($qrSvg),
                    'school_name' => $school->name ?? '',
                    'school_code' => $school->code ?? '',
                    'school_logo' => $schoolLogo,
                    'grade_section' => $gradeSection,
                ];
            });

        $pdf = Pdf::loadView('pdf.qrcodes', ['students' => $students])
            ->setPaper('a4', 'portrait');

        return $pdf->download('codigos-qr-' . Str::slug($school->name ?? 'estudiantes') . '.pdf');
    }

    private function orderedStudentsQuery(int $schoolId)
    {
        return Student::query()
            ->with(['qrCode', 'grade', 'section'])
            ->leftJoin('grades', 'grades.id', '=', 'students.grade_id')
            ->leftJoin('sections', 'sections.id', '=', 'students.section_id')
            ->where('students.school_id', $schoolId)
            ->select('students.*')
            ->orderByRaw('CAST(grades.name AS UNSIGNED) asc')
            ->orderBy('grades.name')
            ->orderBy('sections.name')
            ->orderBy('students.name');
    }

    private function shortGradeName(?string $gradeName): string
    {
        $gradeName = trim((string) $gradeName);

        if ($gradeName === '') {
            return '';
        }

        preg_match('/\d+/', $gradeName, $matches);

        if (! isset($matches[0])) {
            return $gradeName;
        }

        return $matches[0] . 'ro';
    }
}
