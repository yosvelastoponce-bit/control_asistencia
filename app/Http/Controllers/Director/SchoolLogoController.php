<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SchoolLogoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'logo.required' => 'Selecciona una imagen.',
            'logo.image'    => 'El archivo debe ser una imagen.',
            'logo.mimes'    => 'Solo se aceptan imágenes PNG o JPG.',
            'logo.max'      => 'La imagen no debe superar 2MB.',
        ]);

        $schoolId = Auth::guard('app_user')->user()->school_id;
        $school   = School::findOrFail($schoolId);

        // Eliminar logo anterior si existe
        if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
            Storage::disk('public')->delete($school->logo_path);
        }

        // Guardar nuevo logo en storage/app/public/logos/
        $path = $request->file('logo')->store('logos', 'public');

        $school->update(['logo_path' => $path]);

        return response()->json([
            'message'   => 'Logo actualizado correctamente.',
            'logo_url'  => Storage::url($path),
            'logo_path' => $path,
        ]);
    }

    public function destroy()
    {
        $schoolId = Auth::guard('app_user')->user()->school_id;
        $school   = School::findOrFail($schoolId);

        if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
            Storage::disk('public')->delete($school->logo_path);
        }

        $school->update(['logo_path' => null]);

        return response()->json(['message' => 'Logo eliminado.']);
    }
}