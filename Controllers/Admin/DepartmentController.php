<?php

namespace App\Http\Controllers\Admin;



use App\Models\Department;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DepartmentSeat;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        //$departments = DB::table('departments')->get();
        $departments = Department::all();
        //$pages = Page::paginate(20);

        return view('admin.department.show', ['departments' => $departments]);
    }

    public function create() {
        return view('admin.department.create');
    }

    protected function store(Request $request)
    {

        $request->validate(Department::$rules,Department::$messages);
        Department::create($request->all());
        return back()->with('success', 'Department has been created successfully!');
    }

	public function edit($id) {
        $department = Department::find($id);
      return view('admin.department.edit',['department' => $department]);
   }

   
   protected function update($id, Request $request)
    {
        $request->validate(Department::$rules,Department::$messages);
        $data = $request->all();
        $department = Department::find($id);
        $department->fill($data);
        $department->save();
        return back()->with('success', 'Department has been updated successfully!');
    }

	public function destroy($id) {

        Department::destroy($id);

        //$department = Department::find($id);
        //$department->delete();

        return back()->with('success', 'Department has been delete successfully!');
   }
	public function routeGetTotalSeats($id) {
        $department = Department::find($id);

        return response()->json(['total_seats',$department->total_seats]);
   }
	public function routeSeat($id) {
        $seat = DepartmentSeat::find($id);

        return response()->json([$seat]);
   }
	public function routeDept($id) {
       $dept = Department::find($id);

        return response()->json([$dept]);
   }
	
}