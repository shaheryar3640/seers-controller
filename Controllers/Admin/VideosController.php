<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;

class VideosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $videos = Video::all();
        return view('admin.videos.show', compact('videos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $vcategories = VideoCategory::where('enable', 1)->get();
        return view('admin.videos.create', with(['vcategories'=>$vcategories]));
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
        $video = Video::create($request->all());
        $video->slug = str_slug($request->get('title'));
        $video->save();
        if ($video != null) {
            return redirect()->back()->with('success', 'Video has been added successfully!');
//            return view('admin.videos.create')->with('success', 'Video has been added successfully!');
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
        $video = Video::find($id);
        $vcategories = VideoCategory::where('enable', 1)->get();

        return view('admin.videos.edit', with(['video'=>$video, 'vcategories'=>$vcategories]));
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
        $video = Video::find($id);
        $video->title = $request->title;
        $video->slug = str_slug($request->title);
        $video->url = $request->url;
        $video->description = $request->description;
        $video->category = $request->category;
        if($request->get('enabled') == null){
            $video->enable = 0;
        }else{
            $video->enable = 1;
        }
        $video->save();
        
        return back()->with('success', 'Video updated successfully');
        
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
        $video = Video::find($id)->delete();
        
        return back();
    }
}
