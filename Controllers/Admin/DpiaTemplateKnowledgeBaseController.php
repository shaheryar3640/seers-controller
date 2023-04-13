<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DpiaTemplates;
use App\DpiaTemplateQuestion;
use App\DpiaCategory;
use App\DpiaSubCategory;
use App\DpiaKnowledgeBaseCat;
use Auth;
use App\DpiaTemplateKnowledgeBase;
use App\DpiaLogs;

class DpiaTemplateKnowledgeBaseController extends Controller
{
    public function management($id)
    {
        $knowledgebase = DpiaTemplateKnowledgeBase::where(["dpia_template_id" => $id])->orderby('created_at', 'ASC')->get();
        $template = DpiaTemplates::find($id);
        return view('admin.dpia.template.knowledgebase.show', compact(['knowledgebase', 'template']));
    }

    public function create($id)
    {
        $template = DpiaTemplates::find($id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaTemplateQuestion = DpiaTemplateQuestion::where(['admin_approval'=>1, 'enabled'=>1, 'dpia_templates_id'=>$template->id])->get();
        $dpiaKnowledgeBaseCat = DpiaKnowledgeBaseCat::where(['admin_approval'=>1, 'enabled'=>1])->get();
        return view('admin.dpia.template.knowledgebase.create', compact(['template', 'dpiaCategory', 'dpiaSubCategory', 'dpiaTemplateQuestion', 'dpiaKnowledgeBaseCat']));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $templateKnowledgeBase = new DpiaTemplateKnowledgeBase();
        $templateKnowledgeBase->title = $data["title"];
        $templateKnowledgeBase->description = $data["description"];
        $templateKnowledgeBase->dpia_template_id = $data['template_id'];
        $templateKnowledgeBase->dpia_knowledge_base_cat_id = $data["knowledgecategory_id"];

        $templateKnowledgeBase->dpia_sub_category_id = $data["subcategory_id"];
        $templateKnowledgeBase->dpia_template_question_id = $data["question_id"];
        $templateKnowledgeBase->created_by_id = Auth::User()->id;
        $templateKnowledgeBase->updated_by_id = Auth::User()->id;
        if(isset($data["importable"])){
            $templateKnowledgeBase->is_importable= $data["importable"];
        }
        $templateKnowledgeBase->save();

        $log = new DpiaLogs();
        $log->dpia_id = $templateKnowledgeBase->id;
        $log->type = 'Dpia Template Knowledge Base';
        $log->action = 'Add Dpia Template Knowledge Base';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($templateKnowledgeBase);
        $log->save();

        return redirect()->back()->with('success', 'Knowledge Base Category added successfully');
    }

    public function edit($id)
    {
        $templateKnowledgeBase = DpiaTemplateKnowledgeBase::find($id);
        $template = DpiaTemplates::where(['id'=>$id])->first();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaTemplateQuestion = DpiaTemplateQuestion::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaKnowledgeBaseCat = DpiaKnowledgeBaseCat::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.template.knowledgebase.edit', compact(['templateKnowledgeBase', 'dpiaSubCategory', 'dpiaTemplateQuestion', 'dpiaKnowledgeBaseCat']));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $templateKnowledgeBase = DpiaTemplateKnowledgeBase::find($id);
        $templateKnowledgeBase->title = $data["title"];
        $templateKnowledgeBase->description = $data["description"];
        $templateKnowledgeBase->dpia_template_id = $data['template_id'];
        $templateKnowledgeBase->dpia_knowledge_base_cat_id = $data["knowledgecategory_id"];
        $templateKnowledgeBase->dpia_sub_category_id = $data["subcategory_id"];
        $templateKnowledgeBase->dpia_template_question_id = $data["question_id"];
        $templateKnowledgeBase->updated_by_id = Auth::User()->id;
        if(isset($data["importable"])){
            $templateKnowledgeBase->is_importable= $data["importable"];
        }
        $templateKnowledgeBase->save();

        $log = new DpiaLogs();
        $log->dpia_id = $templateKnowledgeBase->id;
        $log->type = 'Dpia Template Knowledge Base';
        $log->action = 'Update Dpia Template Knowledge Base';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($templateKnowledgeBase);
        $log->save();

        return redirect("/admin/dpia_template_knowledge_manage/".$data['template_id']);
    }

    public function enable($id){
        $knowledgebase = DpiaTemplateKnowledgeBase::find($id);
        if(isset($knowledgebase->id)) {
            $knowledgebase->enabled = 1;
            $knowledgebase->save();

            $log = new DpiaLogs();
            $log->dpia_id = $knowledgebase->id;
            $log->type = 'Dpia Template Knowledge Base';
            $log->action = 'Enable Dpia Template Knowledge Base';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($knowledgebase);
            $log->save();
        }
        return back();
    }

    public function disable($id){
        $knowledgebase = DpiaTemplateKnowledgeBase::find($id);
        if(isset($knowledgebase->id)) {
            $knowledgebase->enabled = 0;
            $knowledgebase->save();

            $log = new DpiaLogs();
            $log->dpia_id = $knowledgebase->id;
            $log->type = 'Dpia Template Knowledge Base';
            $log->action = 'Disable Dpia Template Knowledge Base';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($knowledgebase);
            $log->save();
        }
        return back();
    }
}
