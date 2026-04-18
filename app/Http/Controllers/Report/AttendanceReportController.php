<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GeneralAttendance;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportController extends Controller
{
    public function directorExport(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || $user->role !== 'director') {
            abort(403, 'No autorizado.');
        }

        if (!$user->belongsToEnabledSchool()) {
            abort(403, 'El acceso de tu colegio esta bloqueado por el super admin.');
        }

        [$gradeId, $sectionId, $scope, $dateMode, $date] = $this->validateFilters($request, $user->school_id);

        $records = GeneralAttendance::query()
            ->with(['student.grade', 'student.section'])
            ->where('school_id', $user->school_id)
            ->when($dateMode === 'date' && $date, fn ($query) => $query->whereDate('date', $date))
            ->whereHas('student', function ($query) use ($gradeId, $sectionId) {
                if ($gradeId) {
                    $query->where('grade_id', $gradeId);
                }

                if ($sectionId) {
                    $query->where('section_id', $sectionId);
                }
            })
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->get();

        return $this->downloadExcel($records, $scope, 'director', ['date' => $date]);
    }

    public function profesorExport(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || $user->role !== 'teacher') {
            abort(403, 'No autorizado.');
        }

        if (!$user->belongsToEnabledSchool()) {
            abort(403, 'El acceso de tu colegio esta bloqueado por el super admin.');
        }

        [$gradeId, $sectionId, $scope, $dateMode, $date] = $this->validateFilters($request, $user->school_id);

        $records = GeneralAttendance::query()
            ->with(['student.grade', 'student.section'])
            ->where('school_id', $user->school_id)
            ->when($dateMode === 'date' && $date, fn ($query) => $query->whereDate('date', $date))
            ->whereHas('student', function ($query) use ($gradeId, $sectionId) {
                if ($gradeId) {
                    $query->where('grade_id', $gradeId);
                }

                if ($sectionId) {
                    $query->where('section_id', $sectionId);
                }
            })
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->get();

        return $this->downloadExcel($records, $scope, 'profesor-general', ['date' => $date]);
    }

    public function profesorCourseExport(Request $request)
    {
        $user = Auth::guard('app_user')->user();

        if (!$user || $user->role !== 'teacher') {
            abort(403, 'No autorizado.');
        }

        if (!$user->belongsToEnabledSchool()) {
            abort(403, 'El acceso de tu colegio esta bloqueado por el super admin.');
        }

        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            abort(403, 'Profesor no encontrado.');
        }

        [$gradeId, $sectionId, $scope, $dateMode, $date] = $this->validateFilters($request, $user->school_id);
        $subjectId = $this->validateSubjectFilter($request, $teacher->id, $user->school_id);

        $records = Attendance::query()
            ->with(['student.grade', 'student.section', 'schedule.subject'])
            ->when($dateMode === 'date' && $date, fn ($query) => $query->whereDate('date', $date))
            ->whereHas('schedule', function ($query) use ($teacher, $subjectId, $gradeId, $sectionId) {
                $query->where('teacher_id', $teacher->id);

                if ($subjectId) {
                    $query->where('subject_id', $subjectId);
                }

                if ($gradeId) {
                    $query->where('grade_id', $gradeId);
                }

                if ($sectionId) {
                    $query->where('section_id', $sectionId);
                }
            })
            ->whereHas('student', function ($query) use ($user, $gradeId, $sectionId) {
                $query->where('school_id', $user->school_id);

                if ($gradeId) {
                    $query->where('grade_id', $gradeId);
                }

                if ($sectionId) {
                    $query->where('section_id', $sectionId);
                }
            })
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->get();

        return $this->downloadExcel(
            $records,
            $scope,
            'profesor-curso',
            ['include_course' => true, 'date' => $date]
        );
    }

    private function validateFilters(Request $request, int $schoolId): array
    {
        $validated = $request->validate([
            'grade_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'date_filter_mode' => ['nullable', 'in:all,date'],
            'date' => ['nullable', 'date'],
        ]);

        $gradeId = $validated['grade_id'] ?? null;
        $sectionId = $validated['section_id'] ?? null;
        $dateMode = $validated['date_filter_mode'] ?? 'all';
        $date = $validated['date'] ?? null;

        if ($dateMode === 'date' && !$date) {
            abort(422, 'Debes seleccionar una fecha para filtrar.');
        }

        if ($gradeId && !Grade::where('school_id', $schoolId)->whereKey($gradeId)->exists()) {
            abort(422, 'El grado seleccionado no pertenece al colegio.');
        }

        if ($sectionId) {
            $sectionQuery = Section::query()
                ->whereKey($sectionId)
                ->whereHas('grade', fn ($query) => $query->where('school_id', $schoolId));

            if ($gradeId) {
                $sectionQuery->where('grade_id', $gradeId);
            }

            if (!$sectionQuery->exists()) {
                abort(422, 'La seccion seleccionada no es valida para este colegio.');
            }
        }

        $scope = $sectionId ? 'seccion' : ($gradeId ? 'grado' : 'general');

        return [$gradeId, $sectionId, $scope, $dateMode, $date];
    }

    private function validateSubjectFilter(Request $request, int $teacherId, int $schoolId): ?int
    {
        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer'],
        ]);

        $subjectId = $validated['subject_id'] ?? null;

        if (!$subjectId) {
            return null;
        }

        $exists = Subject::query()
            ->where('school_id', $schoolId)
            ->whereKey($subjectId)
            ->whereHas('schedules', fn ($query) => $query->where('teacher_id', $teacherId))
            ->exists();

        if (!$exists) {
            abort(422, 'El curso seleccionado no pertenece a este profesor.');
        }

        return $subjectId;
    }

    private function downloadExcel($records, string $scope, string $actor, array $options = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        $includeCourse = $options['include_course'] ?? false;
        $date = $options['date'] ?? null;

        $headers = ['Nombre', 'DNI', 'Grado', 'Seccion'];
        if ($includeCourse) {
            $headers[] = 'Curso';
        }
        $headers[] = 'Fecha';
        $headers[] = 'Estado';

        $sheet->fromArray($headers, null, 'A1');

        $lastColumn = $includeCourse ? 'G' : 'F';

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
        ]);

        $rowNumber = 2;

        foreach ($records as $record) {
            $row = [
                $record->student?->name ?? '-',
                $record->student?->dni ?? '-',
                $record->student?->grade?->name ?? '-',
                $record->student?->section?->name ?? '-',
            ];

            if ($includeCourse) {
                $row[] = $record->schedule?->subject?->name ?? '-';
            }

            $row[] = optional($record->date)->format('d/m/Y') ?? (string) $record->date;
            $row[] = $this->formatStatus($record->status);

            $sheet->fromArray($row, null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            'reporte-asistencias-%s-%s-%s%s.xlsx',
            $actor,
            $scope,
            now()->format('Y-m-d-His'),
            $date ? '-fecha-' . str_replace('-', '', $date) : ''
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'present' => 'Presente',
            'late' => 'Tarde',
            'absent' => 'Ausente',
            'on_time' => 'Presente',
            default => ucfirst((string) $status),
        };
    }
}
