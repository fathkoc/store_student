<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function create(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'surname' => 'required|string',
                'stdNumber' => 'required|string|size:10|unique:students',
                'grades' => 'required|array',
                'grades.*.code' => 'required|string',
                'grades.*.value' => 'required|integer|between:0,100'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($validatedData) {
            $student = Student::create([
                'name' => $validatedData['name'],
                'surname' => $validatedData['surname'],
                'stdNumber' => $validatedData['stdNumber'],
            ]);

            foreach ($validatedData['grades'] as $grade) {
                $student->grades()->create([
                    'code' => $grade['code'],
                    'value' => $grade['value'],
                ]);
            }
            return response()->json(['message' => 'Student created successfully'], 201);
        }

    }

    public function update(Request $request)
    {
      
        $student = Student::where('stdNumber', $request->stdNumber)->firstOrFail();
        
        $validatedData = $request->validate([
            'name' => 'string',
            'surname' => 'string',
            'stdNumber' => 'required|string|unique:students,stdNumber,' . $student->id,
            'grades' => 'array',
            'grades.*.code' => 'string',
            'grades.*.value' => 'integer|between:0,100',
        ]);


        $student->update([
            'name' => $validatedData['name'],
            'surname' => $validatedData['surname'],
            'stdNumber' => $validatedData['stdNumber'],
        ]);


        $student->grades()->delete();

        foreach ($validatedData['grades'] as $gradeData) {
            $grade = new Grade([
                'code' => $gradeData['code'],
                'value' => $gradeData['value'],
            ]);
            $student->grades()->save($grade);
        }

        return response()->json(['message' => 'Student updated successfully'], 200);
    }

    public function index()
    {
        $students = Student::with('grades')->get();


        foreach ($students as $student) {
            $grades = $student->grades->groupBy('code');
            $avgGrades = $grades->map(function ($grades) {
                return $grades->avg('value');
            });
            $student->grades_avg = $avgGrades;
        }

        return response()->json($students, 200);
    }

}