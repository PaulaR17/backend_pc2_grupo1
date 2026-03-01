<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
 public function getAll() {
    return response()->json(Student::all(), 200, [], JSON_PRETTY_PRINT);
}


   public function getById($id){
      return response()->json(Student::find($id));
   }

   public function create(Request $request){
      $student = Student::create($request->all());
      $student->name=$request->name;
      $student->lastname=$request->lastname;
      $student->email=$request->email;
      $student->save();
      return response()->json($student, 201);
   }

   public function update(Request $request, $id){
      $student = Student::find($id);
      $student->name=$request->name;
      $student->lastname=$request->lastname;
      $student->email=$request->email;
      $student->save();
      return response()->json($student, 200);
   }

   public function delete($id){
      $student = Student::find($id);
      Student::destroy($id);
      return response()->json(null, 204);
   }
}
