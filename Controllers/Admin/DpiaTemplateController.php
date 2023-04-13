<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\DpiaTemplates;
use App\Models\DpiaTemplateQuestion;
use App\Models\DpiaTemplateAnswer;
use App\Models\DpiaTemplateKnowledgeBase;
use App\Models\DpiaTemplateBarReference;

use App\Models\DpiaLogs;
use Auth;

class DpiaTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dpiaTemplates = DpiaTemplates::all();
        return view('admin.dpia.template.show', compact('dpiaTemplates'));
    }


    public function management($id)
    {
        $template = DpiaTemplates::find($id);
        //$dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //$dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //return view('admin.dpia.template.management.show', compact(['template', 'dpiaCategory', 'dpiaSubCategory']));
        return view('admin.dpia.template.management.show', compact(['template']));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.dpia.template.create');
    }

    public function createbyimport()
    {
        $dpiaTemplates = DpiaTemplates::where(['admin_approval'=>1, 'enabled'=>1])->get();
        return view('admin.dpia.template.createbyimport', compact(['dpiaTemplates']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(DpiaTemplates::$rules,DpiaTemplates::$messages);
        $data = $request->all();

//        dd($data["template_id_import"]);
        $template = new DpiaTemplates();
        $template->title = $data['title'];
        $template->sector = $data['sector'];
        if(isset($data['mandatory']) && $data['mandatory'] == true){
            $template->is_mandatory = 1;
        }else{
            $template->is_mandatory = 0;
        }
        //$template->description = $data['desc'];
        $template->type = 'General';
        $template->admin_approval = 1;
        $template->created_by_id = Auth::User()->id;
        $template->updated_by_id = Auth::User()->id;
        $template->enabled = 1;
        $template->save();

//        $log = new DpiaLogs();
//        $log->dpia_id = $template->id;
//        $log->type = 'template';
//        $log->action = 'add template';
//        $log->user_id = Auth::User()->id;
//        $log->json = json_encode($template);
//        $log->save();

        if(isset($data['template_id_import'])){

            $selectedtemplate = DpiaTemplates::find($data["template_id_import"]);

            if(count($selectedtemplate->tmp_questions) > 0) {
                foreach ($selectedtemplate->tmp_questions as $question) {
                    if($question->enabled == 1) {
                        $insQuestion = new DpiaTemplateQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->dpia_templates_id = $template->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = Auth::User()->id;
                        $insQuestion->updated_by_id = Auth::User()->id;
                        $insQuestion->type = $question->type;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->admin_approval = 1;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if(isset($question->rel_bar_reference) && $question->rel_bar_reference != null){
                            $barReference = new DpiaTemplateBarReference();
                            $barReference->dpia_template_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->rel_bar_reference->bar_value;
                            $barReference->created_by_id = Auth::User()->id;
                            $barReference->updated_by_id = Auth::User()->id;
                            $barReference->enabled = $question->rel_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaTemplateAnswer();
                        $insAnswer->description = $question->rel_answer->description;
                        $insAnswer->dpia_template_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = Auth::User()->id;
                        $insAnswer->updated_by_id = Auth::User()->id;
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id'=>$selectedtemplate->id,'dpia_template_question_id'=>$question->id])->get();

                        if(count($knowledgebases)>0){
                            foreach ($knowledgebases as $knowledgebase){
                                if(isset($knowledgebase->id)) {
                                    $insKnowledgebase = new DpiaTemplateKnowledgeBase();
                                    $insKnowledgebase->title = $knowledgebase->title;
                                    $insKnowledgebase->description = $knowledgebase->description;
                                    $insKnowledgebase->dpia_template_id = $template->id;
                                    $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                    $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                    $insKnowledgebase->dpia_template_question_id = $insQuestion->id;
                                    $insKnowledgebase->created_by_id = Auth::User()->id;
                                    $insKnowledgebase->updated_by_id = Auth::User()->id;
                                    $insKnowledgebase->is_importable = $knowledgebase->is_importable;
                                    $insKnowledgebase->sort_order = $knowledgebase->sort_order;
                                    $insKnowledgebase->enabled = 1;
                                    $insKnowledgebase->save();
                                }

                            }
                        }
                    }

//                    $log = new DpiaLogs();
//                    $log->dpia_id = $template->id;
//                    $log->type = 'dpia template';
//                    $log->action = 'dpia template imported to create template';
//                    $log->user_id = Auth::User()->id;
//                    $log->json = json_encode($template);
//                    $log->save();
                }
            }
        }

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_template");
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
        $dpiaTemplate = DpiaTemplates::find($id);
        return view('admin.dpia.template.edit', compact('dpiaTemplate'));
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
        //var_dump($data);
        $template = DpiaTemplates::find($id);
        $template->title = $data['title'];
        $template->sector = $data['sector'];
        if(isset($data['mandatory']) && $data['mandatory'] == true){
            $template->is_mandatory = 1;
        }else{
            $template->is_mandatory = 0;
        }
        //exit();
        //$template->description = $data['desc'];
        //$category->type = 'General';
        $template->admin_approval = 1;
        //$category->created_by_id = Auth::User()->id;
        $template->updated_by_id = Auth::User()->id;
        $template->enabled = 1;
        $template->save();

//        $log = new DpiaLogs();
//        $log->dpia_id = $template->id;
//        $log->type = 'template';
//        $log->action = 'update template';
//        $log->user_id = Auth::User()->id;
//        $log->json = json_encode($template);
//        $log->save();

        //return redirect()->back()->with('sccuess', 'Category Description has been updated');
        return redirect("/admin/dpia_template");
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

        $template = DpiaTemplates::find($id);

        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id'=>$template->id])->get();
        if(count($knowledgebases) > 0){
            foreach ($knowledgebases as $knowledgebase){
                $log = new DpiaLogs();
                $log->dpia_id = $knowledgebase->id;
                $log->type = 'template knowledgebase';
                $log->action = 'delete template knowledgebase';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($knowledgebase);
                $log->save();

                DpiaTemplateKnowledgeBase::destroy($knowledgebase->id);
            }
        }

        $questions = DpiaTemplateQuestion::where(['dpia_templates_id'=>$template->id])->get();
        if(count($questions) > 0){
            foreach($questions as $question){
                $answer = DpiaTemplateAnswer::where(['dpia_template_question_id'=>$question->id])->first();

                if(isset($answer->id)){
//                    $log = new DpiaLogs();
//                    $log->dpia_id = $answer->id;
//                    $log->type = 'template answer';
//                    $log->action = 'delete template answer';
//                    $log->user_id = Auth::User()->id;
//                    $log->json = json_encode($answer);
//                    $log->save();

                    DpiaTemplateAnswer::destroy($answer->id);
                }

                $log = new DpiaLogs();
                $log->dpia_id = $question->id;
                $log->type = 'template question';
                $log->action = 'delete template question';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($question);
                $log->save();

                $barReference = DpiaTemplateBarReference::where(['dpia_template_question_id'=>$question->id])->first();
                if(isset($barReference->id)){
                    DpiaTemplateBarReference::destroy($barReference->id);
                }

                DpiaTemplateQuestion::destroy($question->id);
            }
        }

//        $log = new DpiaLogs();
//        $log->dpia_id = $template->id;
//        $log->type = 'template';
//        $log->action = 'delete template';
//        $log->user_id = Auth::User()->id;
//        $log->json = json_encode($template);
//        $log->save();

        DpiaTemplates::destroy($id);

        return redirect("/admin/dpia_template");
    }

    public function disable($id)
    {
        //dd('destroy');
        $template = DpiaTemplates::find($id);
        if(isset($template->id)) {

            $template->enabled = 0;
            $template->updated_by_id = Auth::User()->id;
            $template->save();

//            $log = new DpiaLogs();
//            $log->dpia_id = $template->id;
//            $log->type = 'template';
//            $log->action = 'disable template';
//            $log->user_id = Auth::User()->id;
//            $log->json = json_encode($template);
//            $log->save();
        }

        return redirect("/admin/dpia_template");
    }

    public function enable($id)
    {
        //dd('destroy');
        $template = DpiaTemplates::find($id);
        if(isset($template->id)) {
            $template->enabled = 1;
            $template->updated_by_id = Auth::User()->id;
            $template->save();

//            $log = new DpiaLogs();
//            $log->dpia_id = $template->id;
//            $log->type = 'template';
//            $log->action = 'disable template';
//            $log->user_id = Auth::User()->id;
//            $log->json = json_encode($template);
//            $log->save();
        }

        return redirect("/admin/dpia_template");
    }

    public function disapprove($id)
    {
        //dd('destroy');
        $template = DpiaTemplates::find($id);

        if(isset($template->id)) {
            $template->admin_approval = 0;
            $template->updated_by_id = Auth::User()->id;
            $template->save();

//            $log = new DpiaLogs();
//            $log->dpia_id = $template->id;
//            $log->type = 'template';
//            $log->action = 'disapprove template';
//            $log->user_id = Auth::User()->id;
//            $log->json = json_encode($template);
//            $log->save();
        }

        return redirect("/admin/dpia_template");
    }

    public function approve($id)
    {
        //dd('destroy');
        $template = DpiaTemplates::find($id);
        if(isset($template->id)) {
            $template->admin_approval = 1;
            $template->updated_by_id = Auth::User()->id;
            $template->save();

//            $log = new DpiaLogs();
//            $log->dpia_id = $template->id;
//            $log->type = 'template';
//            $log->action = 'approve template';
//            $log->user_id = Auth::User()->id;
//            $log->json = json_encode($template);
//            $log->save();
        }

        return redirect("/admin/dpia_template");
    }
}
