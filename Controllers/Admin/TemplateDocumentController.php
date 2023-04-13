<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TemplateDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($tid)
    {

        $template = \App\Models\Template::find($tid);
        $template_name = $template['name'];
        $temp_documents = \App\Models\TemplateDocument::where(['template_id' => $tid, 'enabled' => 1])->orderBy('sort_order', 'desc')->get();
        return view('admin.templates.document.show', compact('temp_documents', 'tid', 'template_name'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($tid)
    {
        return view('admin.templates.document.create', compact('tid'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $tid)
    {

        $temp_document = \App\Models\TemplateDocument::create($request->all());

        if($temp_document != NULL){
            return back()->with('success', 'Document has been created successfully');
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
    public function edit($tid, $id)
    {
        $temp_document = \App\Models\TemplateDocument::find($id);
        return view('admin.templates.document.edit', compact('temp_document', 'tid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $tid, $id)
    {
        $temp_document = \App\Models\TemplateDocument::find($id);

        $temp_document->fill($request->all());
        $temp_document->save();

        return back()->with('success', 'Template Document updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($tid, $id)
    {
        $temp_document = \App\Models\TemplateDocument::where(['id' => $id, 'template_id' => $tid])->delete();

        if($temp_document != NULL){
            return back();
        }
    }
}
