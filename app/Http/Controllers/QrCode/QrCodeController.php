<?php

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\QrCode as QrCodeModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    // Genera QR codes para todos los estudiantes que no tienen uno
    // y devuelve un PDF listo para imprimir
    public function generateQr(Request $request)
    {
        $students = Student::with('qrCode')
            ->orderBy('name')
            ->get();

        // Crear QR si el estudiante no tiene uno
        foreach ($students as $student) {
            if (!$student->qrCode) {
                QrCodeModel::create([
                    'student_id' => $student->id,
                    'uuid'       => Str::uuid(), // genera UUID largo ej: 3f5a8b12-81d4-41f2-b67c-9a13e9f21c21
                    'active'     => true,
                ]);
            }
        }

        // Recargar con los QR ya creados
        $students = Student::with('qrCode')
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                // Generar imagen QR en base64 para el HTML
                // $qrImage = base64_encode(
                //     \QrCode::format('png')
                //         ->size(150)
                //         ->errorCorrection('H')
                //         ->generate($student->qrCode->uuid)
                // );
                // return [
                //     'name'     => $student->name,
                //     'uuid'     => $student->qrCode->uuid,
                //     'qr_image' => $qrImage,
                // ];
                
                $qrSvg = \QrCode::format('svg')
                ->size(150)
                ->errorCorrection('H')
                ->generate($student->qrCode->uuid);
                
                return [
                    'name'   => $student->name,
                    'uuid'   => $student->qrCode->uuid,
                    'qr_svg' => base64_encode($qrSvg), // ← base64 del SVG
                ];
            });

        $pdf = Pdf::loadView('pdf.qrcodes', ['students' => $students])
            ->setPaper('a4', 'portrait');

        return $pdf->download('codigos-qr-estudiantes.pdf');
    }
}