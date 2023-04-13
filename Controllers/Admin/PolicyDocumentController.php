<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorCategory;
use App\Models\PolicyGeneratorPolicy;
use App\Models\PolicyDocument;

class PolicyDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = PolicyGeneratorCategory::all();
        // $policies = PolicyGeneratorPolicy::all();
        // $documents = \App\PolicyDocument::orderBy('policy_id', 'asc')->get();
        // dd($policies);
        return view('admin.policygenerator.policy-document.showcategory', compact('categories'));
        // return "in policy documents";
    }

    public function documentIndex($cid)
    {
        $category = PolicyGeneratorCategory::find($cid);
        $documents = PolicyDocument::where('policy_generator_category_id', $cid)->get();
        $policies = PolicyGeneratorPolicy::where('policy_generator_category_id', $cid)->get();
        return view('admin.policygenerator.policy-document.showpolicy', compact('documents', 'policies', 'category'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cid)
    {   
        // dd("usman");
        $policies = \App\Models\PolicyGeneratorPolicy::where('policy_generator_category_id', $cid)->get();
        return view('admin.policygenerator.policy-document.create', compact('policies', 'cid'));
        // dd($policy);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($cid, Request $request)
    {
        //
        // dd($cid);
        // dd($request->all());
        $document = new \App\Models\PolicyDocument;
        
        $document->fill($request->all());
        $document->policy_generator_category_id = $cid;
        $document->enabled = 1;
        // dd($document);
        $document->save();
        // dd($document);
        return back();
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
    public function edit($cid, $did)
    {
        $document = \App\Models\PolicyDocument::find($did);//->get();
        $policies = \App\Models\PolicyGeneratorPolicy::where('policy_generator_category_id', $cid)->get();
        return view('admin.policygenerator.policy-document.edit', compact('document', 'policies', 'cid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cid, $did)
    {
        // dd($request->all());
        $document = \App\Models\PolicyDocument::where(['id' => $did, 'policy_generator_category_id' => $cid, 'policy_generator_policy_id' => $request->get('policy_generator_policy_id')])->first();
        // dd($document);
        $document->fill($request->all());
        // dd($document);
        $document->save();
        
        return back()->with('success', 'Document Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cid, $did)
    {
        //
        $document = \App\Models\PolicyDocument::where(['policy_generator_category_id' => $cid, 'policy_generator_policy_id' => $did]);

        if($document != null){
            \App\Models\PolicyDocument::destroy($did);
        }
        return back();//->with('success', 'Policy deleted successfully');
    }
}
