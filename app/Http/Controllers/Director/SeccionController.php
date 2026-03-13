<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeccionController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $section = Section::create(['name' => $request->name]);
        return response()->json($section, 201);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $section = Section::findOrFail($id);
        $section->update(['name' => $request->name]);
        return response()->json($section);
    }

    public function destroy(int $id)
    {
        Section::findOrFail($id)->delete();
        return response()->json(['message' => 'Eliminado.']);
    }
}
