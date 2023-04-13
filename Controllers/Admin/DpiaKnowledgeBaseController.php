<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Dpia;
use App\DpiaQuestion;
use App\DpiaCategory;
use App\DpiaSubCategory;
use App\DpiaKnowledgeBaseCat;
use Auth;
use App\DpiaKnowledgeBase;

class DpiaKnowledgeBaseController extends Controller
{
    public function management($id)
    {
        $knowledgebase = DpiaKnowledgeBase::where(["dpia_id" => $id])->orderby('created_at', 'ASC')->get();
        $dpia = Dpia::find($id);
        //$dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //$dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //return view('admin.dpia.dpias.management.show', compact(['dpia', 'dpiaCategory', 'dpiaSubCategory']));

        return view('admin.dpia.dpias.knowledgebase.show', compact(['knowledgebase', 'dpia']));
    }

    public function create($id)
    {
        $dpia = Dpia::find($id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaQuestion = DpiaQuestion::where(['enabled'=>1])->get();
        $dpiaKnowledgeBaseCat = DpiaKnowledgeBaseCat::where(['admin_approval'=>1, 'enabled'=>1])->get();
        return view('admin.dpia.dpias.knowledgebase.create', compact(['dpia', 'dpiaCategory', 'dpiaSubCategory', 'dpiaQuestion', 'dpiaKnowledgeBaseCat']));
    }

    public function store(Request $request)
    {
       // dd($request);
        $data = $request->all();
        $knowledgeBase = new DpiaKnowledgeBase();
        $knowledgeBase->title = $data["title"];
        $knowledgeBase->description = $data["description"];
        $knowledgeBase->dpia_id = $data['dpia_id'];
        $knowledgeBase->dpia_knowledge_base_cat_id = $data["knowledgecategory_id"];

        $knowledgeBase->dpia_sub_category_id = $data["subcategory_id"];
        $knowledgeBase->dpia_question_id = $data["question_id"];
        $knowledgeBase->created_by_id = Auth::User()->id;
        $knowledgeBase->updated_by_id = Auth::User()->id;
        if(isset($data["importable"])){
            $knowledgeBase->is_importable= $data["importable"];
        }
        $knowledgeBase->save();

        return redirect()->back()->with('success', 'Knowledge Base Category added successfully');
    }

    public function edit($id)
    {
        $knowledgeBase = DpiaKnowledgeBase::find($id);
        $dpia = Dpia::where(['id'=>$id])->first();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaQuestion = DpiaQuestion::where(['enabled'=>1])->get();
        $dpiaKnowledgeBaseCat = DpiaKnowledgeBaseCat::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.dpias.knowledgebase.edit', compact(['knowledgeBase', 'dpiaSubCategory', 'dpiaQuestion', 'dpiaKnowledgeBaseCat']));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $knowledgeBase = DpiaKnowledgeBase::find($id);
        $knowledgeBase->title = $data["title"];
        $knowledgeBase->description = $data["description"];
        $knowledgeBase->dpia_id = $data['dpia_id'];
        $knowledgeBase->dpia_knowledge_base_cat_id = $data["knowledgecategory_id"];
        $knowledgeBase->dpia_sub_category_id = $data["subcategory_id"];
        $knowledgeBase->dpia_question_id = $data["question_id"];
        $knowledgeBase->updated_by_id = Auth::User()->id;
        if(isset($data["importable"])){
            $knowledgeBase->is_importable= $data["importable"];
        }
        $knowledgeBase->save();
        return redirect("/admin/dpia_knowledge_manage/".$data['dpia_id']);
    }

    public function enable($id){
        $knowledgebase = DpiaKnowledgeBase::find($id);
        if(isset($knowledgebase->id)) {
            $knowledgebase->enabled = 1;
            $knowledgebase->save();
        }
        return back();
    }

    public function disable($id){
        $knowledgebase = DpiaKnowledgeBase::find($id);
        if(isset($knowledgebase->id)) {
            $knowledgebase->enabled = 0;
            $knowledgebase->save();
        }
        return back();
    }
}
