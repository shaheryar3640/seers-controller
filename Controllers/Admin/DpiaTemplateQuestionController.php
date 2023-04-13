<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\DpiaCategory;
use App\DpiaSubCategory;

use App\DpiaTemplates;
use App\DpiaTemplateQuestion;
use App\DpiaTemplateAnswer;
use App\DpiaTemplateBarReference;
use App\DpiaTemplateKnowledgeBase;
use App\DpiaLogs;
use Auth;

class DpiaTemplateQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $template = DpiaTemplates::find($id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.template.question.create', compact(['template', 'dpiaCategory', 'dpiaSubCategory']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd("testing");
        //$request->validate(DpiaTemplates::$rules,DpiaTemplates::$messages);
        $data = $request->all();

        //dd($data);
        $template = DpiaTemplates::find($data["template_id"]);

        $question = new DpiaTemplateQuestion();
        $question->dpia_category_id = $data["category_id"];
        $question->dpia_sub_category_id = $data["subcategory_id"];
        $question->dpia_templates_id = $template->id;
        $question->description = $data["question"];
        if(isset($data['mandatory']) && $data['mandatory'] == true){
            $question->is_mandatory = 1;
        }else{
            $question->is_mandatory = 0;
        }
        $question->input_type = $data['input_type'] ?? 'textarea';
        $question->tag = $data['tag'] ?? null;
        $question->type = 'General';
        $question->admin_approval = 1;
        $question->created_by_id = Auth::User()->id;
        $question->updated_by_id = Auth::User()->id;
        $question->enabled = 1;
        $question->save();

        $log = new DpiaLogs();
        $log->dpia_id = $question->id;
        $log->type = 'template question';
        $log->action = 'add template question';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($question);
        $log->save();

        if(isset($data['barreference']) && $data['barreference'] == true){
            $barReference = new DpiaTemplateBarReference();
            $barReference->dpia_template_question_id = $question->id;
            $barReference->bar_value = 0;
            $barReference->created_by_id = Auth::User()->id;
            $barReference->updated_by_id = Auth::User()->id;
            $barReference->enabled = 1;
            $barReference->save();
        }

        $answer = new DpiaTemplateAnswer();
        $answer->description = $data["answer"];
        $answer->dpia_template_question_id = $question->id;
        $answer->created_by_id = Auth::User()->id;
        $answer->updated_by_id = Auth::User()->id;
        $answer->enabled = 1;
        $answer->save();

        $log = new DpiaLogs();
        $log->dpia_id = $answer->id;
        $log->type = 'template answer';
        $log->action = 'add template answer';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($answer);
        $log->save();

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_template_management/".$template->id);
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
        //dd("edit");

        $question = DpiaTemplateQuestion::find($id);

        $template = DpiaTemplates::find($question->dpia_templates_id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.template.question.edit', compact(['question','template', 'dpiaCategory', 'dpiaSubCategory']));
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
        //dd($id);
        //dd($data);
        $template = DpiaTemplates::find($data["template_id"]);

        $question = DpiaTemplateQuestion::find($id);
        $question->dpia_category_id = $data["category_id"];
        $question->dpia_sub_category_id = $data["subcategory_id"];
        $question->description = $data['question'];
        if(isset($data['mandatory']) && $data['mandatory'] == true){
            $question->is_mandatory = 1;
        }else{
            $question->is_mandatory = 0;
        }
        $question->input_type = $data['input_type'] ?? 'textarea';
        $question->tag = $data['tag'] ?? null;
        $question->admin_approval = 1;
        $question->updated_by_id = Auth::User()->id;
        $question->enabled = 1;
        $question->save();

        $log = new DpiaLogs();
        $log->dpia_id = $question->id;
        $log->type = 'template question';
        $log->action = 'update template question';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($question);
        $log->save();

        if(isset($data['barreference']) && $data['barreference'] == true){
            $barReference = DpiaTemplateBarReference::where(['dpia_template_question_id'=>$question->id])->first();
            if(!isset($barReference->id)){
                $barReference = new DpiaTemplateBarReference();
                $barReference->dpia_template_question_id = $question->id;
                $barReference->bar_value = 0;
                $barReference->created_by_id = Auth::User()->id;
                $barReference->updated_by_id = Auth::User()->id;
                $barReference->enabled = 1;
                $barReference->save();
            }else{
                $barReference->updated_by_id = Auth::User()->id;
                $barReference->enabled = 1;
                $barReference->save();
            }
        }else{
            $barReference = DpiaTemplateBarReference::where(['dpia_template_question_id'=>$question->id])->first();
            if(isset($barReference->id)){
                DpiaTemplateBarReference::destroy($barReference->id);
            }
        }

        $answer = DpiaTemplateAnswer::where(['dpia_template_question_id'=>$question->id])->first();
        if(isset($answer->id)){
            $answer->description = $data["answer"];
            $answer->updated_by_id = Auth::User()->id;
            $answer->enabled = 1;
            $answer->save();

            $log = new DpiaLogs();
            $log->dpia_id = $answer->id;
            $log->type = 'template answer';
            $log->action = 'update template answer';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($answer);
            $log->save();
        }

        return redirect("/admin/dpia_template_management/".$template->id);
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

        $question = DpiaTemplateQuestion::find($id);

        $template_id = $question->dpia_templates_id;

         if(isset($question->id)){

            $knowledgebase = DpiaTemplateKnowledgeBase::where(['dpia_template_question_id'=>$question->id])->first();

            if(isset($knowledgebase->id)){
                $log = new DpiaLogs();
                $log->dpia_id = $knowledgebase->id;
                $log->type = 'template knowledge base';
                $log->action = 'delete template knowledge base';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($knowledgebase);
                $log->save();

                DpiaTemplateKnowledgeBase::destroy($knowledgebase->id);
            }


            $answer = DpiaTemplateAnswer::where(['dpia_template_question_id'=>$question->id])->first();

            if(isset($answer->id)){
                $log = new DpiaLogs();
                $log->dpia_id = $answer->id;
                $log->type = 'template answer';
                $log->action = 'delete template answer';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($answer);
                $log->save();

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

        return redirect("/admin/dpia_template_management/".$template_id);
    }

    public function disable($id)
    {
        //dd('disable');

        $question = DpiaTemplateQuestion::find($id);

        if(isset($question->id)){
            $question->enabled = 0;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'template question';
            $log->action = 'disable template question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_template_management/".$question->dpia_templates_id);
    }

    public function enable($id)
    {
        //dd('enable');

        $question = DpiaTemplateQuestion::find($id);

        if(isset($question->id)){
            $question->enabled = 1;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'template question';
            $log->action = 'enable template question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_template_management/".$question->dpia_templates_id);
    }

    public function disapprove($id)
    {
        //dd('disapprove');

        $question = DpiaTemplateQuestion::find($id);

        if(isset($question->id)){
            $question->admin_approval = 0;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'template question';
            $log->action = 'disapprove template question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_template_management/".$question->dpia_templates_id);
    }

    public function approve($id)
    {
        //dd('approve');

        $question = DpiaTemplateQuestion::find($id);

        if(isset($question->id)){
            $question->admin_approval = 1;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'template question';
            $log->action = 'approve template question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_template_management/".$question->dpia_templates_id);
    }
}
