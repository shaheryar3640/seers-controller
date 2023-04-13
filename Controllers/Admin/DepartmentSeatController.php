<?php

namespace App\Http\Controllers\Admin;



use App\Models\DepartmentSeat;
use App\Models\Department;
use File;
use Illuminate\Support\Facades\Redirect;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class DepartmentSeatController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        $department_seats = DepartmentSeat::all();
        //$pages = Page::paginate(20);

        return view('admin.departmentSeat.show', ['department_seats' => $department_seats]);
    }

    public function create() {
        $departments = Department::all();
        return view('admin.departmentSeat.create',compact('departments'));
    }

    protected function store(Request $request)
    {
        $request->validate(DepartmentSeat::$rules,DepartmentSeat::$messages);
        DepartmentSeat::create($request->all());
        return back()->with('success', 'Department seat has been created successfully!');
    }

	public function edit($id) {
        $department_seat = DepartmentSeat::find($id);
        $departments = Department::all();
      return view('admin.departmentSeat.edit',compact('departments','department_seat'));
   }


   protected function update($id, Request $request)
    {
        $request->validate(DepartmentSeat::$rules,DepartmentSeat::$messages);
        $data = $request->all();
        //var_dump('in data :',$data);
        $department_seat = DepartmentSeat::find($id);
        $department_seat->fill($data);
        //var_dump('After change :',$department_seat);
        $department_seat->save();
        /*dd('After Save :',$department_seat);
        die();*/
        return Redirect::route('admin.department_seat.index')->with('success', 'Department seat has been updated successfully!');
    }

	public function destroy($id) {

        DepartmentSeat::destroy($id);
        //$department_seat = DepartmentSeat::find($id);
        //$department_seat->delete();

        return back()->with('success', 'Department seat  has been delete successfully!');
   }

}
