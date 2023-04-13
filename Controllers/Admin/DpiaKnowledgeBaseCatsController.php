<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DpiaKnowledgeBaseCat;
use App\Models\DpiaLogs;
use Auth;

class DpiaKnowledgeBaseCatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dpiaKbCategories = DpiaKnowledgeBaseCat::all();
        return view('admin.dpia.kbcategory.show', compact('dpiaKbCategories'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.dpia.kbcategory.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(DpiaKnowledgeBaseCat::$rules,DpiaKnowledgeBaseCat::$messages);
        $data = $request->all();

        //dd($data['name']);
        $kbcategory = new DpiaKnowledgeBaseCat();
        $kbcategory->name = $data['name'];
        if(empty($data['title'])){
            $kbcategory->title = ucfirst($data['name']);
        }else{
            $kbcategory->title = $data['title'];
        }
        //$kbcategory->description = $data['desc'];
        $kbcategory->type = 'General';
        $kbcategory->admin_approval = 1;
        $kbcategory->created_by_id = Auth::User()->id;
        $kbcategory->updated_by_id = Auth::User()->id;
        $kbcategory->enabled = 1;
        $kbcategory->save();

        $log = new DpiaLogs();
        $log->dpia_id = $kbcategory->id;
        $log->type = 'kbcategory';
        $log->action = 'add knowledge base category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($kbcategory);
        $log->save();

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_knowledge_base_cat");
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
        $dpiaKbCategory = DpiaKnowledgeBaseCat::find($id);
        return view('admin.dpia.kbcategory.edit', compact('dpiaKbCategory'));
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
        $data = $request->all();

        $kbcategory = DpiaKnowledgeBaseCat::find($id);
        $kbcategory->name = $data['name'];
        if(empty($data['title'])){
            $kbcategory->title = ucfirst($data['name']);
        }else{
            $kbcategory->title = $data['title'];
        }
        //$kbcategory->description = $data['desc'];
        //$kbcategory->type = 'General';
        $kbcategory->admin_approval = 1;
        //$kbcategory->created_by_id = Auth::User()->id;
        $kbcategory->updated_by_id = Auth::User()->id;
        $kbcategory->enabled = 1;
        $kbcategory->save();

        $log = new DpiaLogs();
        $log->dpia_id = $kbcategory->id;
        $log->type = 'kbcategory';
        $log->action = 'update knowledge base category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($kbcategory);
        $log->save();

        //return redirect()->back()->with('sccuess', 'Category Description has been updated');
        return redirect("/admin/dpia_knowledge_base_cat");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //dd('destroy');

        $kbcategory = DpiaKnowledgeBaseCat::find($id);

        $log = new DpiaLogs();
        $log->dpia_id = $kbcategory->id;
        $log->type = 'kbcategory';
        $log->action = 'delete knowledge base category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($kbcategory);
        $log->save();

        DpiaKnowledgeBaseCat::destroy($id);

        return redirect("/admin/dpia_knowledge_base_cat");
    }

    public function disable($id)
    {
        //dd('destroy');
        $kbcategory = DpiaKnowledgeBaseCat::find($id);
        if(isset($kbcategory->id)) {

            $kbcategory->enabled = 0;
            $kbcategory->updated_by_id = Auth::User()->id;
            $kbcategory->save();

            $log = new DpiaLogs();
            $log->dpia_id = $kbcategory->id;
            $log->type = 'kbcategory';
            $log->action = 'disable knowledge base category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($kbcategory);
            $log->save();
        }

        return redirect("/admin/dpia_knowledge_base_cat");
    }

    public function enable($id)
    {
        //dd('destroy');
        $kbcategory = DpiaKnowledgeBaseCat::find($id);
        if(isset($kbcategory->id)) {
            $kbcategory->enabled = 1;
            $kbcategory->updated_by_id = Auth::User()->id;
            $kbcategory->save();

            $log = new DpiaLogs();
            $log->dpia_id = $kbcategory->id;
            $log->type = 'kbcategory';
            $log->action = 'disable knowledge base category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($kbcategory);
            $log->save();
        }

        return redirect("/admin/dpia_knowledge_base_cat");
    }

    public function disapprove($id)
    {
        //dd('destroy');
        $kbcategory = DpiaKnowledgeBaseCat::find($id);

        if(isset($kbcategory->id)) {

            $kbcategory->admin_approval = 0;
            $kbcategory->updated_by_id = Auth::User()->id;
            $kbcategory->save();

            $log = new DpiaLogs();
            $log->dpia_id = $kbcategory->id;
            $log->type = 'kbcategory';
            $log->action = 'disapprove knowledge base category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($kbcategory);
            $log->save();
        }

        return redirect("/admin/dpia_knowledge_base_cat");
    }

    public function approve($id)
    {
        //dd('destroy');
        $kbcategory = DpiaKnowledgeBaseCat::find($id);
        if(isset($kbcategory->id)) {
            $kbcategory->admin_approval = 1;
            $kbcategory->updated_by_id = Auth::User()->id;
            $kbcategory->save();

            $log = new DpiaLogs();
            $log->dpia_id = $kbcategory->id;
            $log->type = 'kbcategory';
            $log->action = 'approve knowledge base category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($kbcategory);
            $log->save();
        }

        return redirect("/admin/dpia_knowledge_base_cat");
    }
}
