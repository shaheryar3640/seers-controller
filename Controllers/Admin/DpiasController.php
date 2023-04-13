<?php

namespace App\Http\Controllers\Admin;

use App\Models\DpiaTemplateKnowledgeBase;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Dpia;
//use App\DpiaCategory;
//use App\DpiaSubCategory;

use App\Models\DpiaTemplates;
//use App\DpiaTemplateQuestion;
//use App\DpiaTemplateAnswer;

use App\Models\DpiaQuestion;
use App\Models\DpiaAnswer;
use App\Models\DpiaKnowledgeBase;
use App\Models\DpiaBarReference;

use App\Models\DpiaStakeHolder;

use App\Models\DpiaLogs;
use Auth;

class DpiasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $dpias = Dpia::all();
        
        return view('admin.dpia.dpias.show', compact(['dpias']));
    }

    public function management($id)
    {
        $dpia = Dpia::find($id);

        //dd($dpia->dpia_questions);

        //$dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //$dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        //return view('admin.dpia.template.management.show', compact(['template', 'dpiaCategory', 'dpiaSubCategory']));
        return view('admin.dpia.dpias.management.show', compact(['dpia']));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $owners = DpiaStakeHolder::whereIn('user_type', ['owner'])->where(['enabled'=>1])->get();
        if(count($owners) == 0){
            return redirect("/admin/dpia_management");
        }

        $templates = DpiaTemplates::where(['admin_approval'=>1, 'enabled'=>1])->where('Title', '!=', 'Compulsory for DPIA')->get();
        /*foreach($templates as $template){
            echo $template->title;
        }*/

        $editors = DpiaStakeHolder::whereIn('user_type', ['editor', 'owner', 'multi role'])->where(['enabled'=>1])->get();
        /*foreach($editors as $editor){
            echo $editor->name;
        }
        dd("exit");*/

        $reviewers = DpiaStakeHolder::whereIn('user_type', ['owner', 'reviewer', 'multi role'])->where(['enabled'=>1])->get();
        /*foreach($reviewers as $reviewer){
            echo $reviewer->name;
        }*/

        $validators = DpiaStakeHolder::whereIn('user_type', ['owner', 'validator', 'multi role'])->where(['enabled'=>1])->get();
        /*foreach($validators as $validator){
            echo $validator->name;
        }*/

        //dd("create");

        return view('admin.dpia.dpias.create', compact(['templates', 'editors', 'reviewers', 'validators']));
    }


    public function createbyimport()
    {
        $owners = DpiaStakeHolder::whereIn('user_type', ['owner'])->where(['enabled'=>1])->get();
        if(count($owners) == 0){
            return redirect("/admin/dpia_management");
        }

        $dpias = Dpia::where(['enabled'=>1])->get();

        $editors = DpiaStakeHolder::whereIn('user_type', ['editor', 'owner'])->where(['enabled'=>1])->get();

        $reviewers = DpiaStakeHolder::whereIn('user_type', ['owner', 'reviewer'])->where(['enabled'=>1])->get();

        $validators = DpiaStakeHolder::whereIn('user_type', ['owner', 'validator'])->where(['enabled'=>1])->get();

        //dd("create");

        return view('admin.dpia.dpias.createbyimport', compact(['dpias', 'editors', 'reviewers', 'validators']));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $dpia = new Dpia();
        $dpia->name = $data["name"];
        $dpia->title = $data["name"];
        $dpia->editor_id = $data["editor_id"];
        $dpia->reviewer_id = $data["reviewer_id"];
        $dpia->validator_id = $data["validator_id"];
        $dpia->created_by_id = Auth::user()->id;
        $dpia->updated_by_id = Auth::user()->id;
        $dpia->status = 'creation';
        $dpia->last_opened_id = Auth::user()->id;
        $dpia->enabled = 1;
        $dpia->save();

        $log = new DpiaLogs();
        $log->dpia_id = $dpia->id;
        $log->type = 'dpia';
        $log->action = 'create dpia';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($dpia);
        $log->save();

        if(isset($data["template_id"])) {

            $template = DpiaTemplates::find($data["template_id"]);
            // dd($template);
            if (count($template->tmp_questions) > 0) {
                foreach ($template->tmp_questions as $question) {
                    if ($question->enabled == 1) {
                        $insQuestion = new DpiaQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->dpia_id = $dpia->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = Auth::User()->id;
                        $insQuestion->updated_by_id = Auth::User()->id;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if(isset($question->rel_bar_reference) && $question->rel_bar_reference != null){
                            $barReference = new DpiaBarReference();
                            $barReference->dpia_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->rel_bar_reference->bar_value;
                            $barReference->created_by_id = Auth::User()->id;
                            $barReference->updated_by_id = Auth::User()->id;
                            $barReference->enabled = $question->rel_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaAnswer();
                        $insAnswer->description = $question->rel_answer->description;
                        $insAnswer->dpia_id = $dpia->id;
                        $insAnswer->dpia_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = Auth::User()->id;
                        $insAnswer->updated_by_id = Auth::User()->id;
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template->id, 'dpia_template_question_id' => $question->id])->get();

                        if(count($knowledgebases) > 0){
                            foreach ($knowledgebases as $knowledgebase){
                                if (isset($knowledgebase->id)) {
                                    $insKnowledgebase = new DpiaKnowledgeBase();
                                    $insKnowledgebase->title = $knowledgebase->title;
                                    $insKnowledgebase->description = $knowledgebase->description;
                                    $insKnowledgebase->dpia_id = $dpia->id;
                                    $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                    $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                    $insKnowledgebase->dpia_question_id = $insQuestion->id;
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
                }
                foreach ($template->tmp_questions as $question) {
                    if ($question->enabled == 1) {
                        $insQuestion = new DpiaQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->dpia_id = $dpia->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = Auth::User()->id;
                        $insQuestion->updated_by_id = Auth::User()->id;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if(isset($question->rel_bar_reference) && $question->rel_bar_reference != null){
                            $barReference = new DpiaBarReference();
                            $barReference->dpia_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->rel_bar_reference->bar_value;
                            $barReference->created_by_id = Auth::User()->id;
                            $barReference->updated_by_id = Auth::User()->id;
                            $barReference->enabled = $question->rel_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaAnswer();
                        $insAnswer->description = $question->rel_answer->description;
                        $insAnswer->dpia_id = $dpia->id;
                        $insAnswer->dpia_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = Auth::User()->id;
                        $insAnswer->updated_by_id = Auth::User()->id;
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template->id, 'dpia_template_question_id' => $question->id])->get();

                        if(count($knowledgebases) > 0){
                            foreach ($knowledgebases as $knowledgebase){
                                if (isset($knowledgebase->id)) {
                                    $insKnowledgebase = new DpiaKnowledgeBase();
                                    $insKnowledgebase->title = $knowledgebase->title;
                                    $insKnowledgebase->description = $knowledgebase->description;
                                    $insKnowledgebase->dpia_id = $dpia->id;
                                    $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                    $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                    $insKnowledgebase->dpia_question_id = $insQuestion->id;
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
                }

                $template_compulsory = DpiaTemplates::where(['title'=>"Compulsory for DPIA"])->first();

                if (count($template_compulsory->tmp_questions) > 0) {
                    foreach ($template_compulsory->tmp_questions as $question) {
                        if ($question->enabled == 1) {
                            $insQuestion = new DpiaQuestion();
                            $insQuestion->description = $question->description;
                            $insQuestion->dpia_id = $dpia->id;
                            $insQuestion->dpia_category_id = $question->dpia_category_id;
                            $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                            $insQuestion->created_by_id = Auth::User()->id;
                            $insQuestion->updated_by_id = Auth::User()->id;
                            $insQuestion->is_mandatory = $question->is_mandatory;
                            $insQuestion->sort_order = $question->sort_order;
                            $insQuestion->enabled = 1;
                            $insQuestion->save();

                            if (isset($question->rel_bar_reference) && $question->rel_bar_reference != null) {
                                $barReference = new DpiaBarReference();
                                $barReference->dpia_question_id = $insQuestion->id;
                                $barReference->bar_value = $question->rel_bar_reference->bar_value;
                                $barReference->created_by_id = Auth::User()->id;
                                $barReference->updated_by_id = Auth::User()->id;
                                $barReference->enabled = $question->rel_bar_reference->enabled;
                                $barReference->save();
                            }

                            $insAnswer = new DpiaAnswer();
                            $insAnswer->description = $question->rel_answer->description;
                            $insAnswer->dpia_id = $dpia->id;
                            $insAnswer->dpia_question_id = $insQuestion->id;
                            $insAnswer->created_by_id = Auth::User()->id;
                            $insAnswer->updated_by_id = Auth::User()->id;
                            $insAnswer->enabled = 1;
                            $insAnswer->save();

                            $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template_compulsory->id, 'dpia_template_question_id' => $question->id])->get();

                            if (count($knowledgebases) > 0) {
                                foreach ($knowledgebases as $knowledgebase) {
                                    if (isset($knowledgebase->id)) {
                                        $insKnowledgebase = new DpiaKnowledgeBase();
                                        $insKnowledgebase->title = $knowledgebase->title;
                                        $insKnowledgebase->description = $knowledgebase->description;
                                        $insKnowledgebase->dpia_id = $dpia->id;
                                        $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                        $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                        $insKnowledgebase->dpia_question_id = $insQuestion->id;
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
                    }
                }
                $log = new DpiaLogs();
                $log->dpia_id = $dpia->id;
                $log->type = 'dpia template';
                $log->action = 'dpia template imported';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($dpia);
                $log->save();
            }
        }elseif (isset($data['dpia_id_import'])){

            $selectedDpia = Dpia::find($data["dpia_id_import"]);

            if (count($selectedDpia->dpia_questions) > 0) {
                foreach ($selectedDpia->dpia_questions as $question) {
                    if ($question->enabled == 1) {
                        $insQuestion = new DpiaQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->dpia_id = $dpia->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = Auth::User()->id;
                        $insQuestion->updated_by_id = Auth::User()->id;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if(isset($question->dpia_bar_reference) && $question->dpia_bar_reference != null){
                            $barReference = new DpiaBarReference();
                            $barReference->dpia_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->dpia_bar_reference->bar_value;
                            $barReference->created_by_id = Auth::User()->id;
                            $barReference->updated_by_id = Auth::User()->id;
                            $barReference->enabled = $question->dpia_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaAnswer();
                        $insAnswer->description = $question->dpia_answer->description;
                        $insAnswer->dpia_id = $dpia->id;
                        $insAnswer->dpia_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = Auth::User()->id;
                        $insAnswer->updated_by_id = Auth::User()->id;
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaKnowledgeBase::where(['dpia_id' => $selectedDpia->id, 'dpia_question_id' => $question->id])->get();

                        if(count($knowledgebases) > 0){
                            foreach ($knowledgebases as $knowledgebase){
                                if (isset($knowledgebase->id)) {
                                    $insKnowledgebase = new DpiaKnowledgeBase();
                                    $insKnowledgebase->title = $knowledgebase->title;
                                    $insKnowledgebase->description = $knowledgebase->description;
                                    $insKnowledgebase->dpia_id = $dpia->id;
                                    $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                    $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                    $insKnowledgebase->dpia_question_id = $insQuestion->id;
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

                    $log = new DpiaLogs();
                    $log->dpia_id = $dpia->id;
                    $log->type = 'dpia';
                    $log->action = 'dpia add imported';
                    $log->user_id = Auth::User()->id;
                    $log->json = json_encode($dpia);
                    $log->save();
                }
            }

        }

        return redirect("/admin/dpia_management");
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
        $dpias = Dpia::find($id);
        $templates = DpiaTemplates::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $editors = DpiaStakeHolder::whereIn('user_type', ['editor', 'owner', 'multi role'])->where(['enabled'=>1])->get();
        $reviewers = DpiaStakeHolder::whereIn('user_type', ['owner', 'reviewer', 'multi role'])->where(['enabled'=>1])->get();
        $validators = DpiaStakeHolder::whereIn('user_type', ['owner', 'validator', 'multi role'])->where(['enabled'=>1])->get();

        return view('admin.dpia.dpias.edit', compact(['dpias', 'templates', 'editors', 'reviewers', 'validators']));

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
        $dpia = Dpia::find($id);
        $dpia->name = $data["name"];
        $dpia->title = $data["name"];
        $dpia->editor_id = $data["editor_id"];
        $dpia->reviewer_id = $data["reviewer_id"];
        $dpia->validator_id = $data["validator_id"];
        $dpia->created_by_id = Auth::user()->id;
        $dpia->updated_by_id = Auth::user()->id;
        $dpia->last_opened_id = Auth::user()->id;
        $dpia->enabled = 1;
        $dpia->save();

        $log = new DpiaLogs();
        $log->dpia_id = $dpia->id;
        $log->type = 'dpia';
        $log->action = 'update dpia';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($dpia);
        $log->save();




        return redirect("/admin/dpia_management");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $knowledgebases = DpiaKnowledgeBase::where(['dpia_id'=>$id])->get();
        foreach ($knowledgebases as $knowledgebase){
            $log = new DpiaLogs();
            $log->dpia_id = $knowledgebase->id;
            $log->type = 'DPIA knowledge Base';
            $log->action = 'delete DPIA knowledge Base';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($knowledgebase);
            $log->save();
            DpiaKnowledgeBase::destroy($knowledgebase->id);
        }

        $answers = DpiaAnswer::where(['dpia_id'=>$id])->get();
        foreach ($answers as $answer){
                $log = new DpiaLogs();
                $log->dpia_id = $answer->id;
                $log->type = 'DPIA template answer';
                $log->action = 'delete DPIA template answer';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($answer);
                $log->save();
                DpiaAnswer::destroy($answer->id);
        }

        $dpiaQuestions = DpiaQuestion::where(['dpia_id'=>$id])->get();
        foreach ($dpiaQuestions as $dpiaQuestion){
            $log = new DpiaLogs();
            $log->dpia_id = $dpiaQuestion->id;
            $log->type = 'DPIA Question';
            $log->action = 'delete DPIA Question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpiaQuestion);
            $log->save();

            $barReference = DpiaBarReference::where(['dpia_question_id'=>$dpiaQuestion->id])->first();
            if(isset($barReference->id)){
                DpiaBarReference::destroy($barReference->id);
            }

            DpiaQuestion::destroy($dpiaQuestion->id);
        }

        $dpias = Dpia::find($id);
        if(isset($dpias->id)){
            $log = new DpiaLogs();
            $log->dpia_id = $dpias->id;
            $log->type = 'DPIA';
            $log->action = 'delete DPIA';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpias);
            $log->save();
            Dpia::destroy($dpias->id);
        }
        return redirect("/admin/dpia_management/");
    }

    public function disable($id)
    {
        $dpia = Dpia::find($id);

        if(isset($dpia->id)){
            $dpia->enabled = 0;
            $dpia->updated_by_id = Auth::User()->id;
            $dpia->save();

            $log = new DpiaLogs();
            $log->dpia_id = $dpia->id;
            $log->type = 'dpia';
            $log->action = 'disabled dpia';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpia);
            $log->save();
        }

        return redirect("/admin/dpia_management/");
    }

    public function enable($id)
    {
        $dpia = Dpia::find($id);
        if(isset($dpia->id)){
            $dpia->enabled = 1;
            $dpia->updated_by_id = Auth::User()->id;
            $dpia->save();

            $log = new DpiaLogs();
            $log->dpia_id = $dpia->id;
            $log->type = 'dpia';
            $log->action = 'enable dpia';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpia);
            $log->save();
        }

        return redirect("/admin/dpia_management/");
    }
}
