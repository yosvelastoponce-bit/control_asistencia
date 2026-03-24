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
        $schoolId = Auth::guard('app_user')->user()->school_id;
        $school   = \App\Models\School::find($schoolId);

        $students = Student::with('qrCode')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

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

        $students = Student::with('qrCode')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get()
            ->map(function ($student) use ($school, $schoolLogo) {
                $qrSvg = \QrCode::format('svg')
                    ->size(150)
                    ->errorCorrection('H')
                    ->generate($student->qrCode->uuid);

                return [
                    'name'        => $student->name,
                    'qr_svg'      => base64_encode($qrSvg),
                    'school_name' => $school->name ?? '',
                    'school_code' => $school->code ?? '',
                    'school_logo' => $schoolLogo,
                ];
            });

        $pdf = Pdf::loadView('pdf.qrcodes', ['students' => $students])
            ->setPaper('a4', 'portrait');

        return $pdf->download('codigos-qr-' . Str::slug($school->name ?? 'estudiantes') . '.pdf');
    }
}