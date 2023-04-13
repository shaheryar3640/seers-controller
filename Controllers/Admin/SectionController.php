<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorPolicy;
use App\Models\PolicyGeneratorSection;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($cid, $pid)
    {
        // return "in section index with cid : ". $cid . " and pid : " . $pid;
        $policy = PolicyGeneratorPolicy::where(['id' => $pid])->first();
        // dd($policy);
        $pname = $policy->name;
        $sections = PolicyGeneratorSection::where('policy_generator_policy_id', $policy->id)->orderBy('sort_order', 'desc')->get();
        // dd($sections);
        return view('admin.policygenerator.section.show',compact('sections', 'pname', 'cid', 'pid'));
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cid, $pid)
    {
        //dd("In create");
        $sections = PolicyGeneratorSection::where(['policy_generator_policy_id' => $pid, 'enabled' => 1])->get();
        // $policies = PolicyGeneratorPolicy::where('enabled', 1)->get();
        // dd($sections);
        return view('admin.policygenerator.section.create',compact('sections', 'cid', 'pid'));//->with(['sections' => $sections]);        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $cid, $pid)
    {
        $data = $request->all();
        // dd($data);
        $section = PolicyGeneratorSection::create($request->all());        
        // dd($section);
        return redirect(route('admin.section.design',['cid' => $cid, 'pid' => $pid, 'sid' => $section->id]));       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function design($cid, $pid, $sid)
    {
        //dd($pl_id);
        //dd(Input::get('id'));
        //dd($id);
        $section = PolicyGeneratorSection::find($sid);
        //dd($policy);
        $section_data = ($section->section_data);
        //dd($policy_data);
        return view('admin.policygenerator.section.toolkitEditor',[
            'section' => $section,             
            'section_data' => $section_data,
            'cid' => $cid,
            'pid' => $pid  
            ]);
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
    public function edit($cid, $pid, $sid)
    {
        $section = PolicyGeneratorSection::where(['id' => $sid, 'policy_generator_policy_id' => $pid])->first();
        $sections = PolicyGeneratorSection::where(['enabled' => 1, 'policy_generator_policy_id' => $pid])->get();
        
        // dd($sections);
        return view('admin.policygenerator.section.edit', compact('section', 'sections', 'cid', 'pid', 'sid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cid, $pid, $sid)
    {
        //dd($request->all());
        //
        //dd("in section with ".$secId);
        $section = PolicyGeneratorSection::where(['id' => $sid, 'policy_generator_policy_id' => $pid])->first();
        $section->fill($request->all());
        $section->save();
        return response()->json($request->all());
        // return back()->with('success', 'Section has been updated');
        // return "in update with id = ". $id ." and pl_id = " .$pl_id;
    }

    public function updateDesign(Request $request, $cid, $pid, $sid)
    {
        // dd($request->all());
        $section = PolicyGeneratorSection::where(['id' => $sid, 'policy_generator_policy_id' => $pid])->first();
        $section->questions = $request->get('questions');
        $section->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cid, $pid, $sid)
    {   

        $section= PolicyGeneratorSection::where(['id' => $sid, 'policy_generator_policy_id' => $pid])->first();
        // $policy_generator->delete();
        if($section != null){
            PolicyGeneratorSection::destroy($section->id);
        }

        return back();
    }
}
