<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradoController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $grade = Grade::create(['school_id' => $this->schoolId(), 'name' => $request->name]);
        return response()->json($grade, 201);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $grade = Grade::where('id', $id)->where('school_id', $this->schoolId())->firstOrFail();
        $grade->update(['name' => $request->name]);
        return response()->json($grade);
    }

    public function destroy(int $id)
    {
        $grade = Grade::where('id', $id)->where('school_id', $this->schoolId())->firstOrFail();
        $grade->delete();
        return response()->json(['message' => 'Eliminado.']);
    }
}
