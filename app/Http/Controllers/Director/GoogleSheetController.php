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
}