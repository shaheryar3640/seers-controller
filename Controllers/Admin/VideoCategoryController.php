<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\VideoCategory;

class VideoCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $videoCats = VideoCategory::all();
        return view('admin.videocategories.show', compact('videoCats'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $videoCats = VideoCategory::where('enable', 1)->get();
        return view('admin.videocategories.create', with(['videoCats'=>$videoCats]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd('in store');
        $videoCat = VideoCategory::create($request->all());
        $videoCat->slug = str_replace(" ", "-", strtolower($videoCat->title));
        $videoCat->save();
        if($videoCat != null){
            return view('admin.videocategories.create')->with('success', 'Video Category has been added successfully!');
        }
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
        //
        $videoCat = VideoCategory::find($id);

        return view('admin.videocategories.edit', with(['videoCat'=>$videoCat]));
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
        //
        $videoCat = VideoCategory::find($id);
        $videoCat->title = $request->title;
        $videoCat->slug = str_replace(" ", "-", strtolower($request->title));
        if($request->get('enabled') == null){
            $videoCat->enable = 0;
        }else{
            $videoCat->enable = 1;
        }
        $videoCat->save();
        
        return back()->with('success', 'Video Category updated successfully');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $videoCat = VideoCategory::find($id)->delete();
        
        return back();
    }
}
