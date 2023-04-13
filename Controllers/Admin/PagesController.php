<?php

namespace App\Http\Controllers\Admin;



use App\Models\Page;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class PagesController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        //$pages = DB::table('pages')->get();
        $pages = Page::all();

        return view('admin.pages.show', ['pages' => $pages]);
    }

    public function create() {
        return view('admin.pages.create');
    }

    protected function store(Request $request)
    {
        Page::create($request->all());
        return back()->with('success', 'Page has been created successfully!');
    }

	public function edit($id) {
      $page = Page::find($id);
      return view('admin.pages.edit',['page'=>$page]);
   }

   
   protected function update($id, Request $request)
    {
		 $data = $request->all();
		 $page = Page::find($id);
		 $page->fill($data);
		 $page->save();
        return back()->with('success', 'Page has been updated successfully!');
    }

	public function destroy($id) {

        Page::destroy($id);
        return back()->with('success', 'Page has been delete successfully!');
   }
	
}