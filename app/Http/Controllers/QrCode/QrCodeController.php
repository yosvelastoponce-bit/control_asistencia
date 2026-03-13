<?php

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\QrCode as QrCodeModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    public function generateQr(Request $request)
    {
        // Obtener el school_id del director autenticado
        $schoolId = Auth::guard('app_user')->user()->school_id;

        // Solo estudiantes de ese colegio
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

        // Recargar con QR ya creados, mismo filtro por school_id
        $students = Student::with('qrCode')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                $qrSvg = \QrCode::format('svg')
                    ->size(150)
                    ->errorCorrection('H')
                    ->generate($student->qrCode->uuid);

                return [
                    'name'   => $student->name,
                    'uuid'   => $student->qrCode->uuid,
                    'qr_svg' => base64_encode($qrSvg),
                ];
            });

        $pdf = Pdf::loadView('pdf.qrcodes', ['students' => $students])
            ->setPaper('a4', 'portrait');

        return $pdf->download('codigos-qr-estudiantes.pdf');
    }
}