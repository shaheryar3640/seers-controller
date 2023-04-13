<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PressRelease;

class PressReleasesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pressReleases = PressRelease::orderBy("release_on", "desc")->get();
        return view('admin.press-release.show', compact('pressReleases'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.press-release.create');
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
        $pressRelease = PressRelease::create($request->all());
        $pressRelease->slug = str_replace(" ", "-", strtolower($request->title))."-".rand("10000", "99999");
        $pressRelease->save();
        if($pressRelease != null){
            return view('admin.press-release.create')->with('success', 'PressRelease has been added successfully!');
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
        $pressRelease = PressRelease::find($id);

        return view('admin.press-release.edit', with(['pressRelease'=>$pressRelease]));
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
        $pressRelease = PressRelease::find($id);
        $pressRelease->title = $request->title;
        //$pressRelease->slug = str_replace(" ", "-", strtolower($request->title))."-".rand("10000", "99999");
        $pressRelease->description = $request->description;
        $pressRelease->short_description = $request->short_description;
        $pressRelease->release_on = $request->release_on;
        if($request->get('enabled') == null){
            $pressRelease->enable = 0;
        }else{
            $pressRelease->enable = 1;
        }
        $pressRelease->save();
        
        return back()->with('success', 'PressRelease updated successfully');
        
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
        $pressRelease = PressRelease::find($id)->delete();
        
        return back();
    }
}
