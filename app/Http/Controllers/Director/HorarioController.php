<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HorarioController extends Controller
{
    private function teacher(): Teacher
    {
        return Teacher::where('user_id', Auth::guard('app_user')->id())->firstOrFail();
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id'   => 'required|exists:subjects,id',
            'grade_name'   => 'required|string|max:100',
            'section_name' => 'required|string|max:50',
            'day'          => 'required|integer|between:1,7',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ], [
            'subject_id.required'   => 'Selecciona un curso.',
            'subject_id.exists'     => 'El curso no existe.',
            'grade_name.required'   => 'El grado es obligatorio.',
            'section_name.required' => 'La sección es obligatoria.',
            'day.required'          => 'Selecciona un día.',
            'day.between'           => 'El día debe estar entre 1 (Lunes) y 7 (Domingo).',
            'start_time.required'   => 'La hora de inicio es obligatoria.',
            'end_time.required'     => 'La hora de fin es obligatoria.',
            'end_time.after'        => 'La hora de fin debe ser posterior a la de inicio.',
        ]);

        $teacher  = $this->teacher();
        $schoolId = Auth::guard('app_user')->user()->school_id;

        // Buscar o crear grado y sección dentro de la escuela
        $grade = Grade::firstOrCreate(
            ['school_id' => $schoolId, 'name' => $request->grade_name]
        );

        $section = Section::firstOrCreate(
            ['grade_id' => $grade->id, 'name' => ucfirst(strtolower($request->section_name))]
        );

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $request->subject_id,
            'grade_id'   => $grade->id,
            'section_id' => $section->id,
            'day'        => $request->day,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
        ]);

        $schedule->load(['subject', 'grade', 'section']);

        $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];

        return response()->json([
            'id'         => $schedule->id,
            'dia'        => $dias[$schedule->day],
            'subject'    => $schedule->subject->name,
            'grade'      => $schedule->grade->name,
            'section'    => $schedule->section->name,
            'start_time' => $schedule->start_time,
            'end_time'   => $schedule->end_time,
        ], 201);
    }

    public function destroy(int $id)
    {
        $teacher  = $this->teacher();

        $schedule = Schedule::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado.']);
    }
}