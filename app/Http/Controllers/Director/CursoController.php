<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CursoController extends Controller
{
    private function schoolId(): int
    {
        return Auth::guard('app_user')->user()->school_id;
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $subject = Subject::create(['school_id' => $this->schoolId(), 'name' => $request->name]);
        return response()->json($subject, 201);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $subject = Subject::where('id', $id)->where('school_id', $this->schoolId())->firstOrFail();
        $subject->update(['name' => $request->name]);
        return response()->json($subject);
    }

    public function destroy(int $id)
    {
        $subject = Subject::where('id', $id)->where('school_id', $this->schoolId())->firstOrFail();
        $subject->delete();
        return response()->json(['message' => 'Eliminado.']);
    }
}
