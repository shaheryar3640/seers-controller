<?php

namespace App\Http\Controllers\Admin;

use App\Models\CbUsersDomains;
use App\Models\PrivacyCheqDialogue;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allTemplates = \App\Models\Template::orderBy('sort_order', 'asc')->get();
        return view('admin.templates.show', compact('allTemplates'));
    }
    public function privacyCheq_index()
    {
        $PrivacyCheqDialogues = PrivacyCheqDialogue::with('domain')->get();
        return view('admin.privacyCheq.show', compact('PrivacyCheqDialogues'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.templates.create');
    }
    public function privacyCheq_create()
    {
        return view('admin.privacyCheq.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $template = \App\Models\Template::create($request->all());

        if($template != NULL){
            return back()->with('success', 'Template created successfully');
        }
    }
    public function privacyCheq_store(Request $request)
    {
        $template = PrivacyCheqDialogue::create($request->all());

        if($template != NULL){
            return back()->with('success', 'PrivacyCheq Dialogue created successfully');
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
        $template = PrivacyCheqDialogue::find($id);
        return view('admin.templates.edit', compact('template'));
    }
    public function privacyCheq_edit($id)
    {
        $PrivacyCheqDialogue = PrivacyCheqDialogue::find($id);
        return view('admin.privacyCheq.edit', compact('PrivacyCheqDialogue'));
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
        $template = \App\Models\Template::find($id);
        $template->fill($request->all());
        $template->save();

        return back()->with('success', 'Template updated successfully');
    }
    public function privacyCheq_update(Request $request, $id)
    {
        $template = PrivacyCheqDialogue::find($id);
        $template->fill($request->all());
        $template->save();

        return back()->with('success', 'Template updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $template = \App\Models\Template::find($id)->delete();
        if($template != NULL){
            return back();
        }
    }
    public function privacyCheq_destroy($id)
    {
        $template = PrivacyCheqDialogue::find($id)->delete();
        if($template != NULL){
            return back();
        }
    }


    public function search_domain (Request $request){
        $data = [];
        if($request->has('term')){
            $search = $request->term;
            $data =CbUsersDomains::select("id","name")
                ->where('name','LIKE',"%$search%")
                ->get();
        }
        return response()->json($data);
    }

}
