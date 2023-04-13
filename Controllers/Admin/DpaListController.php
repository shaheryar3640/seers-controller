<?php

namespace App\Http\Controllers\Admin;



use App\Models\DpaList;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class DpaListController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        $dpalists = DpaList::paginate(10);
        //$pages = Page::paginate(20);

        return view('admin.dpalist.show', compact('dpalists'));
    }

    public function create() {
        return view('admin.dpalist.create');
    }

    protected function store(Request $request)
    {

        $request->validate(DpaList::$rules,DpaList::$messages);
        DpaList::create($request->all());
        return back()->with('success', 'Dpa list has been created successfully!');
    }

	public function edit($id) {
        $dpalist = DpaList::find($id);
      return view('admin.dpalist.edit',['dpalist' => $dpalist]);
   }

   
   protected function update($id, Request $request)
    {
        $request->validate(DpaList::$rules,DpaList::$messages);
        $data = $request->all();
        $dpalist = DpaList::find($id);
        $dpalist->fill($data);
        $dpalist->save();
        return back()->with('success', 'Dpa list has been updated successfully!');
    }

	public function destroy($id) {

        DpaList::destroy($id);
        return back()->with('success', 'DpaList has been delete successfully!');
   }
	
}