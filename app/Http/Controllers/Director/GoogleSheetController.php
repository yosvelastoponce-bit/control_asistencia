<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleSheetController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'google_sheet_id' => 'required|string|max:255',
        ], [
            'google_sheet_id.required' => 'El ID de Google Sheets es obligatorio.',
        ]);

        $schoolId = Auth::guard('app_user')->user()->school_id;

        School::where('id', $schoolId)->update([
            'google_sheet_id' => trim($request->google_sheet_id),
        ]);

        return response()->json([
            'message'         => 'Google Sheet actualizado correctamente.',
            'google_sheet_id' => $request->google_sheet_id,
        ]);
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'entry_start' => 'required|date_format:H:i',
            'entry_limit' => 'required|date_format:H:i|after:entry_start',
            'entry_end'   => 'required|date_format:H:i|after:entry_limit',
            'auto_process_absences' => 'boolean',
        ], [
            'entry_start.required' => 'La hora de inicio es obligatoria.',
            'entry_limit.required' => 'La hora límite es obligatoria.',
            'entry_limit.after'    => 'La hora límite debe ser posterior a la hora de inicio.',
            'entry_end.required'   => 'La hora de cierre es obligatoria.',
            'entry_end.after'      => 'La hora de cierre debe ser posterior a la hora límite.',
            'entry_end.required'   => 'La hora de cierre es obligatoria.',
            'entry_end.after'      => 'La hora de cierre debe ser posterior a la hora límite.',
        ]);

        $schoolId = Auth::guard('app_user')->user()->school_id;

        School::where('id', $schoolId)->update([
            'entry_start' => $request->entry_start . ':00',
            'entry_limit' => $request->entry_limit . ':00',
            'entry_end'   => $request->entry_end   . ':00',
            'auto_process_absences' => $request->auto_process_absences ?? true,
        ]);

        return response()->json([
            'message'     => 'Horario de entrada actualizado.',
            'entry_start' => $request->entry_start,
            'entry_limit' => $request->entry_limit,
            'entry_end'   => $request->entry_end,
            'auto_process_absences' => $request->auto_process_absences ?? true,
        ]);

    }
        /**
     * Procesar ausencias manualmente desde el dashboard
     */
    
    public function processAbsences(Request $request)
    {
        $schoolId = Auth::guard('app_user')->user()->school_id;
        
        $attendanceController = new \App\Http\Controllers\GeneralAttendanceController();
        
        $processRequest = new \Illuminate\Http\Request();
        $processRequest->merge([
            'school_id' => $schoolId,
            'force' => $request->input('force', false),
        ]);
        
        $response = $attendanceController->processAbsences($processRequest);
        $result = $response->getData();
        
        if ($result->success) {
            return response()->json([
                'success' => true,
                'message' => $result->message,
                'absent_count' => $result->absent_count,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result->message,
            ], 400);
        }
    }

    
}