<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups = Group::orderBy('sort_order', 'desc')->get();
        return view('admin.groups.show', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.groups.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $group = new Group;
        $group->name = $data['name'];
        $group->display_name = $data['display_name'];
        $group->description = $data['description'];
        $group->sort_order = (int) $data['sort_order'];
        $group->is_active = (int) $data['is_active'];
        $group->save();

        return back()->with('success', 'Product Group has been created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $group = Group::find($id);
        if ($group) {
            return view('admin.groups.edit', compact('group'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $group = Group::find($id);
        $data = $request->all();
        $group->name = $data['name'];
        $group->display_name = $data['display_name'];
        $group->description = $data['description'];
        $group->sort_order = (int) $data['sort_order'];
        $group->is_active = (int) $data['is_active'];
        $group->save();

        return back()->with('success', 'Product Group has been updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $group = Group::find($id);
        if($group) {
            $group->delete();
            return back()->with('success', 'Product Group has been deleted successfully');
        }
    }
}
