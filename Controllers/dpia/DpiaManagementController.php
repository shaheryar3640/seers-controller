<?php

namespace App\Http\Controllers\dpia;

use App\Models\Dpia;
use App\Models\DpiaAnswer;
use App\Models\DpiaBarReference;
use App\Models\DpiaCategory;
use App\Models\DpiaCatsCompleteStatus;
use App\Models\DpiaFinalAnswer;
use App\Models\DpiaFinalRemarks;
use App\Models\DpiaGeneralPerson;
use App\Models\DpiaGeneralPersonRemark;
use App\Models\DpiaKnowledgeBase;
use App\Models\DpiaLogs;
use App\Models\DpiaQuestion;
use App\Models\DpiaRemark;
use App\Models\DpiaStakeHolder;
use App\Models\DpiaSubCategory;
use App\Models\DpiaTemplateKnowledgeBase;
use App\Models\DpiaTemplates;
use App\Events\NewDpiaHasCreatedEvent;
use App\Mail\Dpia\DpiaApprovedMail;
use App\Mail\Dpia\DpiaCreateDpiaMail;
use App\Mail\Dpia\DpiaTaskShiftMail;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DpiaManagementController extends Controller
{
    private $message = '';
    private $status_code = 0;

    public function __construct()
    {

    }

    public function index()
    {
        if (request()->ajax()) {
            $currentUser = DpiaStakeHolder::where(['user_id' => auth()->id()])->first();

            if ($currentUser->user_type !== 'owner') {
                $dpia_dpos = DpiaGeneralPerson::where(['stakeholder_id' => $currentUser->id])->whereIn('user_type', ['owner', 'dpo', 'multi role', 'concern person'])->pluck("dpia_id");
                $dpias = Dpia::with('generalPersonRemarks')->where(['enabled' => 1, 'created_by_id' => auth()->id()])
                    ->orWhere(['editor_id' => auth()->id()])
                    ->orWhere(['reviewer_id' => auth()->id()])
                    ->orWhere(['validator_id' => auth()->id()])
                    ->orWhereIn('id', $dpia_dpos)
                    ->get();
            } else {
                $multi_users = DpiaStakeHolder::where(['user_type' => 'multi role', 'created_by_id' => auth()->id()])->pluck('user_id');
                if (count($multi_users) > 0) {
                    $dpias = Dpia::with('generalPersonRemarks')->where(['enabled' => 1, 'created_by_id' => auth()->id()])->orWhereIn('created_by_id', $multi_users)->get();
                } else {
                    $dpias = Dpia::with('generalPersonRemarks')->where(['enabled' => 1, 'created_by_id' => auth()->id()])->get();
                }
            }

            // Check each dpia access level for current user.
            foreach ($dpias as $dpia) {
//                $role = $this->getCurrentRole($dpia, $currentUser->user_id);
                $dpia->access_mode = $this->getCurrentRole($dpia, $currentUser->user_id);
            }
            return response()->json(['dpias' => $dpias, 'category_structure' => $this->getCategoryStructure()], 200);
        } else {
            $this->message = 'Baq Request';
            $this->status_code = 400;
            return response()->json(['message' => $this->message], $this->status_code);
        }
    }

    public function getDpia($id)
    {
        if (request()->ajax()) {
            $dpia = Dpia::find($id);
            $dpiaCats = $this->getCategoryStructure($id);
            $curRole = $this->getCurrentRole($dpia, auth()->id());

            $this->message = 'Data Found';
            $this->status_code = 200;

            return response(['message' => $this->message, 'dpia_categories' => $dpiaCats, 'current_role' => $curRole], $this->status_code);

        } else {
            $this->message = 'Baq Request';
            $this->status_code = 400;
            return response(['message' => $this->message], $this->status_code);
        }
    }

    public function getCategoryStructure($dpia_id = 0) {
        $categories = DpiaCategory::select('id', 'name', 'description')->where(['enabled' => 1])->get();

        if($categories->count() > 0) {
            $count = 1;
            foreach ($categories as $category) {
                $category->count = $count;

                /* if conditions are just to hide the graph and mapping sections
                   because these sections are not built yet.
                */
                $sub_categories = $category->sub_categories()->select('id', 'name', 'description', 'has_evaluation_comment')->get();
                foreach ($sub_categories as $sub_category) {
                    if ($sub_category->has_evaluation_comment === 1) {
                        $sub_category->final_remark = $sub_category->finalRemarks()->where('dpia_id', '=', $dpia_id)->first();
                    }
                }

//                foreach ($sub_categories as $sub_category) {
//                    $sub_category->question = DpiaQuestion::where(['dpia_id' => $dpia_id, 'dpia_sub_category_id' => $sub_category->id, 'enabled' => 1])->get();
//                }
//                if (strtolower($category->name) === 'risks') {
//                    $sub_categories = $category->sub_categories()->select('id', 'name', 'description')->where('name', '!=', 'Risks Overview')->get();
//                } else if (strtolower($category->name) === 'validation') {
//                    $sub_categories = $category->sub_categories()->select('id', 'name', 'description')->whereNotIn('name', ['Action plan', 'Risk mapping'])->get();
//                } else {
//                    $sub_categories = $category->sub_categories()->select('id', 'name', 'description')->get();
//                }
                $category->sub_categories = $sub_categories;
                $count++;
            }
        }
        return $categories;
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), Dpia::getRules(), Dpia::getMessages());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors() ], 400);
        }

        $record = $request->all();
        $dpia = new Dpia();
        $dpia->name = $record['title'];
        $dpia->title = $record['title'];
        $dpia->editor_id = $record['editor_id'];
        $dpia->reviewer_id = $record['reviewer_id'];
        $dpia->validator_id = $record['validator_id'];
        $dpia->created_by_id = auth()->id();
        $dpia->updated_by_id = auth()->id();
        $dpia->status = 'creation';
        $dpia->last_opened_id = auth()->id();
        $dpia->template_id = $record['template_id'];
        $dpia->enabled = 1;
        $dpia->save();

        $dpo = DpiaStakeHolder::where(['user_id' => $record['dpo_id']])->first();
        if (isset($dpo->id)) {
            $general_person = new DpiaGeneralPerson;
            $general_person->dpia_id = $dpia->id;
            $general_person->stakeholder_id = $dpo->id;
            $general_person->user_type = 'dpo';
            $general_person->created_by_id = auth()->id();
            $general_person->enabled = 1;
            $general_person->save();
        }
        if ($record['concern_person_id'] !== null && $record['concern_person_id'] !== 0) {
            $stake_holder = DpiaStakeHolder::where(['user_id' => $record['concern_person_id']])->first();
            $general_person = new DpiaGeneralPerson;
            $general_person->dpia_id = $dpia->id;
            $general_person->stakeholder_id = $stake_holder['id'];
            $general_person->user_type = 'concern person';
            $general_person->created_by_id = auth()->id();
            $general_person->enabled = 1;
            $general_person->save();
        }

//        if(count($data['concern_person_id']) > 0) {
//            foreach ($data['concern_person_id'] as $concern_person){
//
//            }
//        }
//        $log = new DpiaLogs();
//        $log->dpia_id = $dpia->id;
//        $log->type = 'dpia';
//        $log->action = 'create dpia';
//        $log->user_id = auth()->id();
//        $log->json = json_encode($dpia);
//        $log->save();

        if (isset($record['template_id'])) {

            $template = DpiaTemplates::find($record['template_id']);

            if (count($template->tmp_questions) > 0) {
                foreach ($template->tmp_questions as $question) {
                    if ($question->enabled == 1) {
                        $insQuestion = new DpiaQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->input_type = $question->input_type;
                        $insQuestion->tag = $question->tag;
                        $insQuestion->dpia_id = $dpia->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = $question->created_by_id;
                        $insQuestion->updated_by_id = $question->updated_by_id;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if (isset($question->rel_bar_reference) && $question->rel_bar_reference != null) {
                            $barReference = new DpiaBarReference();
                            $barReference->dpia_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->rel_bar_reference->bar_value;
                            $barReference->created_by_id = $question->created_by_id;
                            $barReference->updated_by_id = $question->updated_by_id;
                            $barReference->enabled = $question->rel_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaAnswer();
                        $insAnswer->description = $question->rel_answer->description;
                        $insAnswer->updated_description = $question->rel_answer->updated_description;
                        $insAnswer->dpia_id = $dpia->id;
                        $insAnswer->dpia_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = auth()->id();
                        $insAnswer->updated_by_id = auth()->id();
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template->id, 'dpia_template_question_id' => $question->id])->get();

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
                                    $insKnowledgebase->created_by_id = auth()->id();
                                    $insKnowledgebase->updated_by_id = auth()->id();
                                    $insKnowledgebase->is_importable = $knowledgebase->is_importable;
                                    $insKnowledgebase->sort_order = $knowledgebase->sort_order;
                                    $insKnowledgebase->enabled = 1;
                                    $insKnowledgebase->save();
                                }
                            }
                        }
                    }
                }

                $template_compulsory = DpiaTemplates::where(['title' => "Compulsory for DPIA"])->first();

                if (count($template_compulsory->tmp_questions) > 0) {
                    foreach ($template_compulsory->tmp_questions as $question) {
                        if ($question->enabled == 1) {
                            $insQuestion = new DpiaQuestion();
                            $insQuestion->description = $question->description;
                            $insQuestion->input_type = $question->input_type;
                            $insQuestion->tag = $question->tag;
                            $insQuestion->dpia_id = $dpia->id;
                            $insQuestion->dpia_category_id = $question->dpia_category_id;
                            $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                            $insQuestion->created_by_id = auth()->id();
                            $insQuestion->updated_by_id = auth()->id();
                            $insQuestion->is_mandatory = $question->is_mandatory;
                            $insQuestion->sort_order = $question->sort_order;
                            $insQuestion->enabled = 1;
                            $insQuestion->save();

                            if (isset($question->rel_bar_reference) && $question->rel_bar_reference != null) {
                                $barReference = new DpiaBarReference();
                                $barReference->dpia_question_id = $insQuestion->id;
                                $barReference->bar_value = $question->rel_bar_reference->bar_value;
                                $barReference->created_by_id = auth()->id();
                                $barReference->updated_by_id = auth()->id();
                                $barReference->enabled = $question->rel_bar_reference->enabled;
                                $barReference->save();
                            }

                            $insAnswer = new DpiaAnswer();
                            $insAnswer->description = $question->rel_answer->description;
                            $insAnswer->updated_description = $question->rel_answer->updated_description;
                            $insAnswer->dpia_id = $dpia->id;
                            $insAnswer->dpia_question_id = $insQuestion->id;
                            $insAnswer->created_by_id = auth()->id();
                            $insAnswer->updated_by_id = auth()->id();
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
                                        $insKnowledgebase->created_by_id = auth()->id();
                                        $insKnowledgebase->updated_by_id = auth()->id();
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
//                $log = new DpiaLogs();
//                $log->dpia_id = $dpia->id;
//                $log->type = 'dpia template';
//                $log->action = 'dpia template imported';
//                $log->user_id = auth()->id();
//                $log->json = json_encode($dpia);
//                $log->save();
            }
        }

        event(new NewDpiaHasCreatedEvent($dpia, Auth::User()));
        return response()->json(['message' => 'DPIA created successfully'], 200);
    }

    public function duplicate($id)
    {
        $dpia = Dpia::find($id);
        
        if ($dpia) {          
            $new_dpia = Dpia::create([
                'name' => $dpia->name . '(Copy)',
                'title' => $dpia->title . '(Copy)',
                'editor_id' => $dpia->editor_id,
                'reviewer_id' => $dpia->reviewer_id,
                'validator_id' => $dpia->validator_id,
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
                'status' => 'creation',
                'last_opened_id' => auth()->id(),
                'template_id' => $dpia->template_id,
                'enabled' => 1,
            ]);
            $general_persons = DpiaGeneralPerson::where(['dpia_id' => $dpia->id])->get();
            if ($general_persons && $general_persons->count() > 0) {
                foreach ($general_persons as $general_person) {
                    $new_general = new DpiaGeneralPerson;
                    $new_general->dpia_id = $new_dpia->id;
                    $new_general->stakeholder_id = $general_person->stakeholder_id;
                    $new_general->user_type = $general_person->user_type;
                    $new_general->created_by_id = auth()->id();
                    $new_general->enabled = 1;
                    $new_general->save();
                }
            }

            $template = DpiaTemplates::find($dpia->template_id);

            if (count($template->tmp_questions) > 0) {
                foreach ($template->tmp_questions as $question) {
                    if ($question->enabled == 1) {
                        $insQuestion = new DpiaQuestion();
                        $insQuestion->description = $question->description;
                        $insQuestion->input_type = $question->input_type;
                        $insQuestion->tag = $question->tag;
                        $insQuestion->dpia_id = $new_dpia->id;
                        $insQuestion->dpia_category_id = $question->dpia_category_id;
                        $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                        $insQuestion->created_by_id = $question->created_by_id;
                        $insQuestion->updated_by_id = $question->updated_by_id;
                        $insQuestion->is_mandatory = $question->is_mandatory;
                        $insQuestion->sort_order = $question->sort_order;
                        $insQuestion->enabled = 1;
                        $insQuestion->save();

                        if (isset($question->rel_bar_reference) && $question->rel_bar_reference != null) {
                            $barReference = new DpiaBarReference();
                            $barReference->dpia_question_id = $insQuestion->id;
                            $barReference->bar_value = $question->rel_bar_reference->bar_value;
                            $barReference->created_by_id = $question->created_by_id;
                            $barReference->updated_by_id = $question->updated_by_id;
                            $barReference->enabled = $question->rel_bar_reference->enabled;
                            $barReference->save();
                        }

                        $insAnswer = new DpiaAnswer();
                        $insAnswer->description = $question->rel_answer->description;
                        $insAnswer->updated_description = $question->rel_answer->updated_description;
                        $insAnswer->dpia_id = $new_dpia->id;
                        $insAnswer->dpia_question_id = $insQuestion->id;
                        $insAnswer->created_by_id = auth()->id();
                        $insAnswer->updated_by_id = auth()->id();
                        $insAnswer->enabled = 1;
                        $insAnswer->save();

                        $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template->id, 'dpia_template_question_id' => $question->id])->get();

                        if (count($knowledgebases) > 0) {
                            foreach ($knowledgebases as $knowledgebase) {
                                if (isset($knowledgebase->id)) {
                                    $insKnowledgebase = new DpiaKnowledgeBase();
                                    $insKnowledgebase->title = $knowledgebase->title;
                                    $insKnowledgebase->description = $knowledgebase->description;
                                    $insKnowledgebase->dpia_id = $new_dpia->id;
                                    $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                    $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                    $insKnowledgebase->dpia_question_id = $insQuestion->id;
                                    $insKnowledgebase->created_by_id = auth()->id();
                                    $insKnowledgebase->updated_by_id = auth()->id();
                                    $insKnowledgebase->is_importable = $knowledgebase->is_importable;
                                    $insKnowledgebase->sort_order = $knowledgebase->sort_order;
                                    $insKnowledgebase->enabled = 1;
                                    $insKnowledgebase->save();
                                }
                            }
                        }
                    }
                }

                $template_compulsory = DpiaTemplates::where(['title' => "Compulsory for DPIA"])->first();

                if (count($template_compulsory->tmp_questions) > 0) {
                    foreach ($template_compulsory->tmp_questions as $question) {
                        if ($question->enabled == 1) {
                            $insQuestion = new DpiaQuestion();
                            $insQuestion->description = $question->description;
                            $insQuestion->input_type = $question->input_type;
                            $insQuestion->tag = $question->tag;
                            $insQuestion->dpia_id = $new_dpia->id;
                            $insQuestion->dpia_category_id = $question->dpia_category_id;
                            $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                            $insQuestion->created_by_id = auth()->id();
                            $insQuestion->updated_by_id = auth()->id();
                            $insQuestion->is_mandatory = $question->is_mandatory;
                            $insQuestion->sort_order = $question->sort_order;
                            $insQuestion->enabled = 1;
                            $insQuestion->save();

                            if (isset($question->rel_bar_reference) && $question->rel_bar_reference != null) {
                                $barReference = new DpiaBarReference();
                                $barReference->dpia_question_id = $insQuestion->id;
                                $barReference->bar_value = $question->rel_bar_reference->bar_value;
                                $barReference->created_by_id = auth()->id();
                                $barReference->updated_by_id = auth()->id();
                                $barReference->enabled = $question->rel_bar_reference->enabled;
                                $barReference->save();
                            }

                            $insAnswer = new DpiaAnswer();
                            $insAnswer->description = $question->rel_answer->description;
                            $insAnswer->updated_description = $question->rel_answer->updated_description;
                            $insAnswer->dpia_id = $new_dpia->id;
                            $insAnswer->dpia_question_id = $insQuestion->id;
                            $insAnswer->created_by_id = auth()->id();
                            $insAnswer->updated_by_id = auth()->id();
                            $insAnswer->enabled = 1;
                            $insAnswer->save();

                            $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template_compulsory->id, 'dpia_template_question_id' => $question->id])->get();

                            if (count($knowledgebases) > 0) {
                                foreach ($knowledgebases as $knowledgebase) {
                                    if (isset($knowledgebase->id)) {
                                        $insKnowledgebase = new DpiaKnowledgeBase();
                                        $insKnowledgebase->title = $knowledgebase->title;
                                        $insKnowledgebase->description = $knowledgebase->description;
                                        $insKnowledgebase->dpia_id = $new_dpia->id;
                                        $insKnowledgebase->dpia_knowledge_base_cat_id = $knowledgebase->dpia_knowledge_base_cat_id;
                                        $insKnowledgebase->dpia_sub_category_id = $knowledgebase->dpia_sub_category_id;
                                        $insKnowledgebase->dpia_question_id = $insQuestion->id;
                                        $insKnowledgebase->created_by_id = auth()->id();
                                        $insKnowledgebase->updated_by_id = auth()->id();
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
            }

            event(new NewDpiaHasCreatedEvent($new_dpia, Auth::User()));
            return response()->json(['message' => 'DPIA duplicated successfully'], 200);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }

    public function destroy($id)
    {
        if(request()->ajax()) {

            $knowledge_bases = DpiaKnowledgeBase::where(['dpia_id' => $id])->get();
            foreach ($knowledge_bases as $knowledge_base) {
                $knowledge_base->delete();
            }

            $answers = DpiaAnswer::where(['dpia_id' => $id])->get();
            foreach ($answers as $answer){
                $answer->delete();
            }

            $questions = DpiaQuestion::where(['dpia_id' => $id])->get();
            foreach ($questions as $question){

                if ($question->bar_reference !== null) {
                    $question->bar_reference->delete();
                }

                if($question->dpia_final_answer !== null) {
                    $question->dpia_final_answer->delete();
                }

                if ($question->dpia_remarks->count() > 0) {
                    foreach ($question->dpia_remarks as $comment) {
                        $comment->delete();
                    }
                }

                if ($question->finalRemark !== null) {
                    $question->finalRemark->delete();
                }

                $question->delete();
            }

            $general_persons = DpiaGeneralPerson::where(['dpia_id' => $id])->get();
            foreach ($general_persons as $general_person){
                $general_person->delete();
            }

            $dpia_general_person_remarks = DpiaGeneralPersonRemark::where(['dpia_id' => $id])->get();
            if ($dpia_general_person_remarks->count() > 0) {
                foreach ($dpia_general_person_remarks as $remark) {
                    $remark->delete();
                }
            }

            $dpia_final_remarks = DpiaFinalRemarks::where(['dpia_id' => $id])->get();
            if ($dpia_final_remarks->count() > 0) {
                foreach ($dpia_final_remarks as $remark) {
                    $remark->delete();
                }
            }

            $dpia_complete_categories = DpiaCatsCompleteStatus::where(['dpia_id' => $id])->get();
            if ($dpia_complete_categories->count() > 0) {
                foreach ($dpia_complete_categories as $complete_category) {
                    $complete_category->delete();
                }
            }

            Dpia::destroy($id);
//            if(isset($dpias->id)){
//                $log = new DpiaLogs();
//                $log->dpia_id = $dpias->id;
//                $log->type = 'DPIA';
//                $log->action = 'delete DPIA';
//                $log->user_id = auth()->id();
//                $log->json = json_encode($dpias);
//                $log->save();
//                Dpia::destroy($dpias->id);
//            }

            $this->message = 'DPIA deleted successfully';
            $this->status_code = 200;
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response()->json(['message' => $this->message], $this->status_code);
    }

    public function getCurrentRole(Dpia $dpia, $user_id) {
        $current_role = '';
        switch ($dpia->status) {
            case 'editor':
            case 'creation':
                $current_role = ($dpia->editor_id == $user_id) ? 'editor' : 'owner';
                break;
            case 'reviewer':
            case 'editor-complete':
                $current_role = ($dpia->reviewer_id == $user_id) ? 'reviewer' : 'owner';
                break;
            case 'dpo':
            case 'reviewer-complete':
                if ($dpia->dpia_dpo != null) {
                    $stake_holder = DpiaStakeHolder::where(['user_id' => $user_id, 'enabled' => 1])->first();
                    $current_role = $dpia->dpia_dpo->stakeholder_id === $stake_holder->id ? 'dpo' : 'owner';
                } else {
                    $current_role = 'owner';
                }
                break;
            case 'concern-person':
            case 'dpo-complete':
                if ($dpia->dpia_concern_person != null) {
                    $stake_holder = DpiaStakeHolder::where(['user_id' => $user_id, 'enabled' => 1])->first();
                    $current_role = $dpia->dpia_concern_person->stakeholder_id === $stake_holder->id ? 'concern person' : 'owner';
                }else{
                    $current_role = 'owner';
                }
                break;
            case 'validator':
            case 'concern-person-complete':
                $current_role = ($dpia->validator_id == $user_id) ? 'validator' : 'owner';
                break;
            default:
                $current_role = 'owner';
                break;
        }
        return $current_role;
    }

    public function getDpiaStructure ($id)
    {
        if ( request()->ajax() ) {
            $dpia = Dpia::find($id);
            $categories = [];
            $sub_categories = [];
            if ($dpia && $dpia->dpia_questions->count() > 0) {
                foreach ($dpia->dpia_questions as $question) {
                    $question_ids = [];
                    if($question->dpia_category != null) {
                        if(!in_array($question->dpia_category->name, $categories)) {
                            array_push($categories, $question->dpia_category->name);
                        }
                    }

                    if($question->dpia_subcategory != null) {
                        if(!array_key_exists($question->dpia_subcategory->name, $sub_categories)) {
                            $sub_categories[$question->dpia_subcategory->name] = $question->id;
                        } else {
                            $question_ids = (array) $sub_categories[array_key_last($sub_categories)];
                            array_push($question_ids, $question->id);
                            $sub_categories[array_key_last($sub_categories)] = $question_ids;
                        }
                    }
                }
            }

            return response(['data' => $sub_categories], 200);
        } else {
            return response(['message' => 'Bad Request'], 400);
        }
    }

    public function getDpiaQuestionStructure($dpia_id, $sub_category_id)
    {
        $title = $text = '';
        $parent_sub_cat_id = 0;
        $questions = $knowledge_bases = $sub_category = null;
        $show_popup = false;
        if (request()->ajax()) {
            $sub_category = $this->getSubCategory($dpia_id, $sub_category_id);
            $dpia = Dpia::find($dpia_id);
            $dependent_sub_cats = [
                'Illegitimate access to data',
                'Unwanted modification of data',
                'Data disappearance'
            ];

            if (in_array($sub_category->name, $dependent_sub_cats)) {
                $parent_sub_category = DpiaSubCategory::Category()->where('name', '=', 'Planned or Existing Measures')->first();
                $parent_sub_cat_questions = $this->getQuestions($dpia_id, $parent_sub_category->id);
                if($parent_sub_cat_questions->count() == 0) {
                    $questions = $this->getQuestions($dpia_id, $parent_sub_category->id);
                    $knowledge_bases = $this->getKnowledgeBase($dpia_id, $parent_sub_category->id);
                    if ($parent_sub_category->has_evaluation_comment === 1) {
                        $parent_sub_category->final_remark = $parent_sub_category->finalRemarks()->where('dpia_id', '=', $dpia_id)->first();
                    }
                    $sub_category = $parent_sub_category;
                    $show_popup = true;
                    $title = 'Detailed existing or planned measures';
                    $text = 'Before evaluating risks, you must report the existing or planned measures.';
                    $parent_sub_cat_id = $parent_sub_category->id;
                } else {
                    $questions = $this->getQuestions($dpia_id, $sub_category_id);
                    $knowledge_bases = $this->getKnowledgeBase($dpia_id, $sub_category_id);
                    $sub_category = $this->getSubCategory($dpia->id, $sub_category_id);
                }
            } else if ($sub_category->name === 'Dpo and data subjects opinions' && ($dpia->status !== 'reviewer-complete' && $dpia->status !== 'dpo' && $dpia->status !== 'dpo-complete' && $dpia->status !== 'concern-person-complete' && $dpia->status !== 'approved')) {
                $parent_sub_category = DpiaSubCategory::Category()->where('name', '=', 'Overview')->first();
                if ($parent_sub_category->has_evaluation_comment === 1) {
                    $parent_sub_category->final_remark = $parent_sub_category->finalRemarks()->where('dpia_id', '=', $dpia_id)->first();
                }
                $questions = $this->getQuestions($dpia_id, $parent_sub_category->id);
                $knowledge_bases = $this->getKnowledgeBase($dpia_id, $parent_sub_category->id);
                $sub_category = $parent_sub_category;
                $show_popup = true;
                if (in_array($dpia->status, ['creation', 'editor', 'editor-complete', 'reviewer', 'reviewer-complete'])) {
                    $title = 'In Process';
                    $text = 'DPIA is in process. Please wait until completion of other entities.';
                } else {
                    $title = 'Limited Access';
                    $text = 'This section belongs to DPO and Concern Person Only.';
                }
                $parent_sub_cat_id = $parent_sub_category->id;
            } else if (strtolower($sub_category->name) === 'action plan') {
//                $questions = $this->getActionPlanResults($dpia_id);
                $knowledge_bases = [];
            } else if (strtolower($sub_category->name) === 'risk mapping') {
//                $questions = $this->getRiskMappingResults($dpia_id);
                $knowledge_bases = [];
            } elseif (strtolower($sub_category->name) === 'risks overview') {
                $questions = $this->getRiskOverviewResults($dpia_id);
                $knowledge_bases = [];
            } else {
                $questions = $this->getQuestions($dpia_id, $sub_category_id);
                $knowledge_bases = $sub_category->name === 'Planned or Existing Measures'
                                    ? $this->getPlannedKnowledgeBases()
                                    : $this->getKnowledgeBase($dpia_id, $sub_category_id);
                $sub_category = $this->getSubCategory($dpia_id, $sub_category_id);
            }

            $this->message = 'Data found';
            $this->status_code = 200;

        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
            return response(['message' => $this->message], $this->status_code);
        }

        return response([
            'questions' => $questions,
            'knowledge_bases' => $knowledge_bases,
            'final_remark' => $sub_category->final_remark ?? null,
//            'sub_category' => $sub_category,
//            'tracked' => $this->getTrackedCategories($sub_category, $dpia_id),
            'popup' => $show_popup,
            'progress' => $this->getProgress($dpia_id),
            'parent_sub_category' => $parent_sub_cat_id,
            'title' => $title,
            'text' => $text,
            'complete_status' => in_array($dpia->status, ['creation', 'editor']) ? $this->getCategoryCompleteStatus($dpia->id) : []
        ], $this->status_code);
    }

    private function getTrackedCategories($_sub_category, $dpia_id)
    {
        $categories = $this->getCategoryStructure($dpia_id);
        $all_sub_categories = $tracked = [];
        $count = 1;
        foreach ($categories as $category) {
            foreach ($category->sub_categories as $sub_category) {
                $sub_category->count = $count;
                $all_sub_categories[] = $sub_category;
            }
            $count++;
        }

        for ($i = 0; $i < count($all_sub_categories); $i++) {
            if ($all_sub_categories[$i]['name'] === $_sub_category['name']) {
                if ($i === 0) {
                    $tracked['previous'] = null;
                    $tracked['next'] = $all_sub_categories[$i + 1];
                } else if ($i === (count($all_sub_categories) - 1)) {
                    $tracked['previous'] = $all_sub_categories[$i - 1];
                    $tracked['next'] = null;
                } else {
                    $tracked['previous'] = $all_sub_categories[$i - 1];
                    $tracked['next'] = $all_sub_categories[$i + 1];
                }
            }
        }
        return $tracked;
    }

    public function getQuestions ($dpia_id, $sub_category_id) {
        return DpiaQuestion::with('knowledge_bases', 'finalRemark')->where([
            'dpia_sub_category_id' => $sub_category_id,
            'dpia_id' => $dpia_id,
            'enabled' => 1
        ])->get();
    }

    public function getKnowledgeBase ($dpia_id, $sub_category_id) {
        return DpiaKnowledgeBase::where([
            'dpia_sub_category_id' => $sub_category_id,
            'dpia_id' => $dpia_id,
            'enabled' => 1
        ])->select(['id', 'is_importable', 'dpia_id', 'dpia_question_id', 'title', 'description', 'dpia_knowledge_base_cat_id', 'dpia_sub_category_id'])->get();
    }

    public function getSubCategory ($dpia_id, $sub_category_id) {
        $sub_category = DpiaSubCategory::Category()->where('id', '=', $sub_category_id)->first();
        if ($sub_category->has_evaluation_comment === 1) {
            $sub_category->final_remark = $sub_category->finalRemarks()->where('dpia_id', '=', $dpia_id)->first();
        }
        return $sub_category;
    }

    public function saveAnswer($answer) {
        $final_answer = DpiaFinalAnswer::firstOrCreate([
            'dpia_question_id' => $answer['question_id']
        ]);

        if ($answer['question_type'] === 'multiselect') {
            $final_answer->description = $answer['tags'];

            if ($final_answer->description === null) {
                DpiaFinalAnswer::destroy($final_answer->id);
            }
        } else {
            $final_answer->description = $answer['answer'];
        }
        if($final_answer) {
            $final_answer->answer_status = 'editor';
            $final_answer->progress = 'editor';
            $final_answer->dpia_id = $answer['dpia_id'];
            $final_answer->dpia_question_id = $answer['question_id'];
            $final_answer->filled_by_id = auth()->id();
            $final_answer->save();
        }


        if ($answer['question_type'] === 'range') {
            $range_question = DpiaBarReference::firstOrCreate([
                'dpia_question_id' => $answer['question_id']
            ]);
            $range_question->bar_value = $answer['range_value'];
            $range_question->created_by_id = auth()->id();
            $range_question->updated_by_id = auth()->id();
            $range_question->save();
        }

        $dpia = Dpia::find($answer['dpia_id']);

        if ($dpia && $dpia->status != 'editor') {
            $dpia->status = 'editor';
            $dpia->save();
        }

        $sub_category = DpiaSubCategory::Category()->where('id', '=', $answer['sub_category_id'])->first();

        $dpia_questions = DpiaQuestion::where([
            'dpia_id' => $answer['dpia_id'],
            'dpia_category_id' => $sub_category->dpia_category->id,
            'dpia_sub_category_id' => $sub_category->id
        ])->get();

        $total_answers = 0;
        $total_questions = $dpia_questions->count();
        foreach ($dpia_questions as $question) {
            if($question->dpia_final_answer != null) {
                $total_answers++;
            }
        }

        if ($total_answers === $total_questions) {
            $dpia_complete_category = DpiaCatsCompleteStatus::firstOrCreate([
                'dpia_id' => $answer['dpia_id'],
                'dpia_category_id' => $sub_category->dpia_category->id,
                'dpia_sub_category_id'=> $sub_category->id
            ]);
            $dpia_complete_category->user_id = auth()->id();
            $dpia_complete_category->status = 'complete';
            $dpia_complete_category->save();
        }
//        if ($request->ajax()) {
//
//            $this->message = 'Answer saved successfully';
//            $this->status_code = 200;
//
//        } else {
//            $this->message = 'Bad Request';
//            $this->status_code = 400;
//
//            return response(['message' => $this->message], $this->status_code);
//        }
//        return response([
//            'message' => $this->message,
////            'questions' => $this->getQuestions($dpia->id, $sub_category->id),
////            'knowledge_bases' => $this->getKnowledgeBase($dpia->id, $sub_category->id),
//            'progress' => $this->getProgress($dpia->id),
////            'complete_status' => $this->getCategoryCompleteStatus($dpia->id)
//        ], $this->status_code);
    }

    public function saveBulkAnswer(Request $request) {

        if (!$request->ajax()) {
            return response(['message' => 'Bad Request', 'code' => '', 'data' => ''], 400);
        }
        $submit_type = $request->get('submit_type');

        $answers = $request->get('final_answers');
//        $user_id = auth()->id();
        if (count($answers) > 0) {
            foreach($answers as $answer) {

                if ($submit_type === 'editor') {
                    if (isset($answer['is_new']) && $answer['is_new'] === true) {
                        $this->saveQuestion($answer);
                    } else {
                        $this->saveAnswer($answer);
                    }
                } else {
                    $this->submitFinalEvaluation($answer);
                }

//                $final_answer = DpiaFinalAnswer::firstOrCreate([
//                    'dpia_question_id' => $answer['question_id']
//                ]);
//
//                if ($answer['question_type'] === 'multiselect') {
//                    $final_answer->description = $answer['tags'];
//
//                    if ($final_answer->description === null) {
//                        DpiaFinalAnswer::destroy($final_answer->id);
//                    }
//                } else {
//                    $final_answer->description = $answer['answer'];
//                }
//                if($final_answer) {
//                    $final_answer->answer_status = 'editor';
//                    $final_answer->progress = 'editor';
//                    $final_answer->dpia_id = $answer['dpia_id'];
//                    $final_answer->dpia_question_id = $answer['question_id'];
//                    $final_answer->filled_by_id = $user_id;
//                    $final_answer->save();
//                }
//
//
//                if ($answer['question_type'] === 'range') {
//                    $range_question = DpiaBarReference::firstOrCreate([
//                        'dpia_question_id' => $answer['question_id']
//                    ]);
//                    $range_question->bar_value = $answer['range_value'];
//                    $range_question->created_by_id = $user_id;
//                    $range_question->updated_by_id = $user_id;
//                    $range_question->save();
//                }
//
//                $dpia = Dpia::find($answer['dpia_id']);
//
//                if ($dpia && $dpia->status != 'editor') {
//                    $dpia->status = 'editor';
//                    $dpia->save();
//                }
//
//                $sub_category = DpiaSubCategory::Category()->where('id', '=', $answer['sub_category_id'])->first();
//
//                $dpia_questions = DpiaQuestion::where([
//                    'dpia_id' => $answer['dpia_id'],
//                    'dpia_category_id' => $sub_category->dpia_category->id,
//                    'dpia_sub_category_id' => $sub_category->id
//                ])->get();
//
//                $total_answers = 0;
//                $total_questions = $dpia_questions->count();
//                foreach ($dpia_questions as $question) {
//                    if($question->dpia_final_answer != null) {
//                        $total_answers++;
//                    }
//                }
//
//                if ($total_answers === $total_questions) {
//                    $dpia_complete_category = DpiaCatsCompleteStatus::firstOrCreate([
//                        'dpia_id' => $answer['dpia_id'],
//                        'dpia_category_id' => $sub_category->dpia_category->id,
//                        'dpia_sub_category_id'=> $sub_category->id
//                    ]);
//                    $dpia_complete_category->user_id = $user_id;
//                    $dpia_complete_category->status = 'complete';
//                    $dpia_complete_category->save();
//                }
            }
        }

        $comments = $request->get('comment_list');
        if (count($comments) > 0) {
            foreach($comments as $comment) {
                $this->storeCommentForAnswer($comment);
            }
        }

        $this->message = 'Saved successfully';
        $this->status_code = 200;
        return response([
            'message' => $this->message,
//            'questions' => $this->getQuestions($dpia->id, $sub_category->id),
//            'knowledge_bases' => $this->getKnowledgeBase($dpia->id, $sub_category->id),
            'progress' => $this->getProgress($request->get('dpia_id')),
//            'complete_status' => $this->getCategoryCompleteStatus($dpia->id)
        ], $this->status_code);
    }

    public function storeComment(Request $request) {

        if ($request->ajax()) {

            $final_answer = DpiaFinalAnswer::where([
                'dpia_question_id' => $request->get('question_id')
            ])->first();

            $comment = new DpiaRemark;
            $comment->remarks = $request->get('comment_body');
            $comment->dpia_id = $request->get('dpia_id');
            $comment->dpia_question_id = $request->get('question_id');
            $comment->dpia_final_answer_id = $final_answer->id;
            $comment->comment_keyword = $request->get('comment_by');
            $comment->dpia_stake_holder_id = auth()->id();
            $comment->enabled = 1;
            $comment->save();

            $this->message = 'Comment added successfully';
            $this->status_code = 200;
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response(['message' => $this->message], $this->status_code);
    }

    private function storeCommentForAnswer ($data) {
        $final_answer = DpiaFinalAnswer::where([
            'dpia_question_id' => $data['question_id']
        ])->first();

        $comment = new DpiaRemark;
        $comment->remarks = $data['comment_body'];
        $comment->dpia_id = $data['dpia_id'];
        $comment->dpia_question_id = $data['question_id'];
        $comment->dpia_final_answer_id = $final_answer->id;
        $comment->comment_keyword = $data['comment_by'];
        $comment->dpia_stake_holder_id = auth()->id();
        $comment->enabled = 1;
        $comment->save();
    }

    private function saveQuestion($data) {

        $user_id = auth()->id();
        $dpia = Dpia::find($data["dpia_id"]);
        $question = $data['question'];
        $sub_category = DpiaSubCategory::find($data["sub_category_id"]);

        $new_question = DpiaQuestion::firstOrCreate([
            // 'id' => $question['id'],
            'dpia_sub_category_id' => $sub_category->id,
            'dpia_id' => $dpia->id,
        ]);
        $new_question->description = $question["description"] ?? null;
        $new_question->is_mandatory = 0;
        $new_question->dpia_id = $dpia->id;
        $new_question->dpia_category_id = $sub_category->dpia_category->id;
        $new_question->dpia_sub_category_id = $sub_category->id;
        $new_question->created_by_id = $user_id;
        $new_question->updated_by_id = $user_id;
        $new_question->enabled = 1;
        $new_question->sort_order = 0;
        $new_question->save();

        if ($new_question->dpia_answer == null) {
            $new_question->dpia_answer()->create([
                'description' => $question['dpia_answer']['description'],
                'dpia_id' => $dpia->id,
                'created_by_id' => $user_id,
                'updated_by_id' => $user_id,
                'enabled' => 1
            ]);
        }

        if($new_question->dpia_final_answer == null) {
            $new_question->dpia_final_answer()->create([
                'description' => $data['answer'],
                'answer_status' => 'editor',
                'progress' => 'editor',
                'dpia_id' => $dpia->id,
                'dpia_question_id' => $new_question->id,
                'filled_by_id' => $user_id,
            ]);
        }

        $dpia_questions = DpiaQuestion::where([
            'dpia_id' => $data['dpia_id'],
            'dpia_category_id' => $sub_category->dpia_category->id,
            'dpia_sub_category_id' => $sub_category->id
        ])->get();

        $total_answers = 0;
        $total_questions = $dpia_questions->count();
        foreach ($dpia_questions as $question) {
            if($question->dpia_final_answer != null) {
                $total_answers++;
            }
        }

        if ($total_answers === $total_questions) {
            $dpia_complete_category = DpiaCatsCompleteStatus::firstOrCreate([
                'dpia_id' => $data['dpia_id'],
                'dpia_category_id' => $sub_category->dpia_category->id,
                'dpia_sub_category_id'=> $sub_category->id
            ]);
            $dpia_complete_category->user_id = $user_id;
            $dpia_complete_category->status = 'complete';
            $dpia_complete_category->save();
        }
    }

    public function deleteQuestion ($id) {

        $question = DpiaQuestion::find($id);
        $dpia_id = $sub_category_id = 0;

        if(request()->ajax()) {
            if(!is_null($question)) {
                $dpia_id = $question->dpia_id;
                $sub_category_id = $question->dpia_sub_category_id;

                if ($question->dpia_answer !== null) {
                    $question->dpia_answer->delete();
                }

                if($question->dpia_remarks->count() > 0) {
                    foreach ($question->dpia_remarks as $comment) {
                        $comment->delete();
                    }
                }

                if($question->dpia_final_answer !== null) {

                    $dpia = Dpia::find($dpia_id);
                    $questions = $dpia->dpia_questions()->where('tag', '=', 'Measures')->get();
                    if($questions->count() > 0) {
                        foreach ($questions as $new_question) {
                            if($new_question->dpia_final_answer !== null) {
                                if(strpos($new_question->dpia_final_answer->description, $question->description) !== false) {
                                    $new_question->dpia_final_answer->description = str_replace($question->description, '', $new_question->dpia_final_answer->description);
                                    $new_question->dpia_final_answer()->save($new_question->dpia_final_answer);
                                }
                            }
                        }
                    }

                    $question->dpia_final_answer->delete();
                }

                if ($question->bar_reference !== null) {
                    $question->bar_reference->delete();
                }

                if($question->finalRemark !== null) {
                    $question->finalRemark->delete();
                }

                $question->delete();


                $dpia_questions = DpiaQuestion::where([
                    'dpia_id' => $dpia_id,
                    'dpia_sub_category_id' =>$sub_category_id
                ])->count();

                if ($dpia_questions === 0) {
                    $dpia_complete_category = DpiaCatsCompleteStatus::where([
                        'dpia_id' => $dpia_id,
                        'dpia_sub_category_id' =>$sub_category_id
                    ])->first();

                    $dpia_complete_category && $dpia_complete_category->delete();
                }

                $this->message = 'Question deleted successfully!';
                $this->status_code = 200;
            } else {
                $this->message = 'Question not found';
                $this->status_code = 401;
            }
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response([
            'message' => $this->message,
//            'questions' => $this->getQuestions($dpia_id, $sub_category_id)
        ], $this->status_code);
    }

    private function getProgress($dpia_id) {
        $dpia = Dpia::find($dpia_id);
        $data = null;
        switch($dpia->status) {
            case 'creation':
            case 'editor':
                $data['editor_completed'] = $dpia->dpia_questions->count() === $dpia->dpia_final_answers->count();
                break;
            case 'editor-complete':
            case 'reviewer':
                $data['reviewer_completed'] = $dpia->dpia_questions->count() === $dpia->finalRemarks->count();
                if ( $data['reviewer_completed'] ) {
                    $to_correct_remarks = DpiaFinalRemarks::where(['dpia_id' => $dpia_id, 'status' => 'to_correct'])->count();
                    $data['move_ahead'] = $to_correct_remarks > 0 ? false : true;
                } else {
                    $data['move_ahead'] = false;
                }
                break;
            case 'reviewer-complete':
            case 'dpo':
                $data['dpo_completed'] = $dpia->generalPersonRemarks()->where(['created_by_id' => auth()->id(), 'remark_by' => 'dpo'])->count() > 0;
                break;
            case 'dpo-complete':
            case 'concern person':
                $data['concern_person_completed'] = $dpia->generalPersonRemarks()->where(['created_by_id' => auth()->id(), 'remark_by' => 'concern person'])->count() > 0;
                break;
            case 'concern-person-complete':
            case 'validator':
                $data['validator_completed'] = false;
                break;

            default:
        }

        return $data;
    }

    public function askForProcess(Request $request)
    {
        if ($request->ajax()) {
            $dpia = Dpia::find($request->get('dpia_id'));

            switch($request->get('request_from')) {
                case 'editor':
                    $dpia->status = 'editor-complete';
                    $this->message = 'DPIA sent for reviewing';
                    $this->sendDpiaTaskShiftEmail('reviewer', $dpia, true);
                    break;
                case 'reviewer':
                    if($request->get('move_ahead')) {
                        $dpia->status = 'reviewer-complete';
                        $this->message = 'DPIA sent to Data Protection Adviser';
                        $this->sendDpiaTaskShiftEmail('dpo', $dpia, true);
                    } else {
                        $dpia->status = 'editor';
                        $this->message = 'DPIA sent back to Editor';
                        $this->sendDpiaTaskShiftEmail('editor', $dpia, false);
                    }
                    break;
                case 'dpo':
                    if($dpia->dpia_concern_person !== null) {
                        $dpia->status = 'dpo-complete';
                        $this->message = 'DPIA sent to Concern Person';
                        $this->sendDpiaTaskShiftEmail('concern person', $dpia, true);
                    } else {
                        $dpia->status = 'concern-person-complete';
                        $this->message = 'DPIA sent to Validator';
                        $this->sendDpiaTaskShiftEmail('validator', $dpia, true);
                    }
                    break;
                case 'concern person':
                    $dpia->status = 'concern-person-complete';
                    $this->message = 'DPIA sent to Validator';
                    $this->sendDpiaTaskShiftEmail('validator', $dpia, true);
                    break;
                case 'validator':

                    break;
                default:
                    $dpia->status = 'creation';
                    $this->message = 'DPIA in creation mode';
            }
            $dpia->save();
            $this->status_code = 200;
        } else {
            $this->message = 'Baq Request';
            $this->status_code = 400;
        }
        return response()->json(['message' => $this->message], $this->status_code);
    }

    public function loadRemarks(Request $request) {
        $evaluation = DpiaFinalRemarks::where([
            'dpia_id' => $request->get('dpia_id'),
            'sub_category_id' => $request->get('sub_category_id'),
            'evaluated_by' => 'reviewer',
            'created_by_id' => auth()->id()
        ])->first();
        return response(['message' => 'Data found', 'data' => $evaluation], 200);
    }

    public function submitFinalEvaluation($evaluation) {

        $dpia = Dpia::find($evaluation['dpia_id']);
        if ($dpia->status === 'editor-complete' && $dpia->status !== 'reviewer') {
            $dpia->status = 'reviewer';
            $dpia->save();
        }

        /* Category Level Evaluation */
        if ($evaluation['question_id'] <= -1) {
            $questions = $this->getQuestions($dpia->id, $evaluation['sub_category_id']);
            foreach ($questions as $question) {
                $evaluationRemark = DpiaFinalRemarks::firstOrCreate([
                    'dpia_id' => $dpia->id,
                    'sub_category_id' => $evaluation['sub_category_id'],
                    'question_id' => $question->id,
                    'created_by_id' => auth()->id()
                ]);
                $evaluationRemark->evaluation_remarks = $evaluation['evaluation_remark'];
                $evaluationRemark->action_plan_remarks = $evaluation['action_plan_remark'];
                $evaluationRemark->status = $evaluation['evaluation_status'];
                $evaluationRemark->evaluated_by = $evaluation['evaluated_by'];
                $evaluationRemark->created_by_id = auth()->id();
                $evaluationRemark->updated_by_id = auth()->id();
                $evaluationRemark->risk_severity = $evaluation['risk_severity'];
                $evaluationRemark->risk_likelihood = $evaluation['risk_likelihood'];
                $evaluationRemark->save();
            }
        } else {
            /* Question level Evaluation */
            $evaluationRemark = DpiaFinalRemarks::firstOrCreate([
                'dpia_id' => $dpia->id,
                'sub_category_id' => $evaluation['sub_category_id'],
                'question_id' => $evaluation['question_id'],
                'created_by_id' => auth()->id()
            ]);
            $evaluationRemark->evaluation_remarks = $evaluation['evaluation_remark'];
            $evaluationRemark->action_plan_remarks = $evaluation['action_plan_remark'];
            $evaluationRemark->status = $evaluation['evaluation_status'];
            $evaluationRemark->evaluated_by = $evaluation['evaluated_by'];
            $evaluationRemark->created_by_id = auth()->id();
            $evaluationRemark->updated_by_id = auth()->id();
            $evaluationRemark->risk_severity = $evaluation['risk_severity'];
            $evaluationRemark->risk_likelihood = $evaluation['risk_likelihood'];
            $evaluationRemark->save();
        }

//        $this->message = 'Evaluation saved successfully';
//        $this->status_code = 200;
//
//        return response([
//            'message' => $this->message,
//            'progress' => $this->getProgress($evaluation->get('dpia_id'))
//        ], $this->status_code);
    }

    public function storeGeneralPersonRemarks(Request $request) {

        if($request->ajax()) {
            $remark = DpiaGeneralPersonRemark::firstOrCreate([
                'created_by_id' => auth()->id(),
                'dpia_id' => $request->get('dpia_id'),
                'remark_by' => $request->get('comment_by')
            ]);

            $remark->dpia_id = $request->get('dpia_id');
            $remark->status = $request->get('status');
            $remark->remark = $request->get('comment_body');
            $remark->remark_by = $request->get('comment_by');
            $remark->created_by_id = auth()->id();
            $remark->updated_by_id = auth()->id();
            $remark->enabled = 1;
            $remark->save();

            $this->message = 'Remarks saved successfully';
            $this->status_code = 200;
        } else {
            $this->message = 'Baq Request';
            $this->status_code = 400;
        }
        return response(['message' => $this->message, 'progress' => $this->getProgress($request->get('dpia_id'))], $this->status_code);
    }

    public function storeValidatorComments(Request $request) {
        if ($request->ajax()) {
            $remark = DpiaGeneralPersonRemark::firstOrCreate([
                'created_by_id' => auth()->id(),
                'dpia_id' => $request->get('dpia_id'),
                'remark_by' => $request->get('comment_by')
            ]);

            $remark->remark = $request->get('status') === 'reject' ? $request->get('comment') : null;
            $remark->adjustments = $request->get('adjustments') ?? null;
            $remark->status = $request->get('status');
            $remark->created_by_id = auth()->id();
            $remark->updated_by_id = auth()->id();
            $remark->enabled = 1;
            $remark->save();

            $dpia = Dpia::find($remark->dpia_id);

            if ($remark->status === 'reject') {
                if ($dpia->status !== 'reviewer' && $dpia->status !== 'editor-complete') {
                    $dpia->status = 'reviewer';
                    $dpia->save();

                    $this->sendDpiaTaskShiftEmail('reviewer', $dpia, false);
                    $this->message = 'DPIA rejected successfully';
                }
            } elseif ($remark->status === 'approved_direct') {
                $dpia->status = 'approved';
                $dpia->save();

                $this->sendDpiaTaskShiftEmail('approved', $dpia, false);
                $this->message = 'DPIA approved successfully';
            }
            $this->status_code = 200;
        } else {
            $this->message = 'Baq Request';
            $this->status_code = 400;
        }

        return response(['message' => $this->message], $this->status_code);
    }

    // This function is being used in PrivacyAssessmentController
    // Do make changes carefully

    public function getActionPlanResults($dpia_id) {
        $dpia = Dpia::find($dpia_id);
        $fundamental_questions = $risk_sub_categories = $planned_or_existing_measures = [];
        if($dpia) {
            if ($dpia->dpia_questions->count() > 0) {
                $questions = $dpia->dpia_questions()->with('finalRemark')->get();
                foreach ($questions as $question) {

                    if (strtolower($question->dpia_category->name) === 'fundamental principles') {
                        array_push($fundamental_questions, $question);
                    }

                    if (strtolower($question->dpia_subcategory->name) === 'planned or existing measures') {
                        array_push($planned_or_existing_measures, $question);
                    }

                    if (strtolower($question->dpia_category->name) === 'risks') {
                        if ($question->dpia_subcategory->has_evaluation_comment === 1) {
                            $remark = DpiaFinalRemarks::where([
                                'dpia_id' => $dpia->id,
                                'sub_category_id' => $question->dpia_subcategory->id
                            ])->first();
                            if($remark) {
                                $risk_sub_categories[$question->dpia_subcategory->name] = $remark;
                            } else {
                                $risk_sub_categories[$question->dpia_subcategory->name] = null;
                            }
                        }
                    }
                }
            }
        }
        $data[] = $fundamental_questions;
        $data[] = $risk_sub_categories;
        $data[] = $planned_or_existing_measures;
        return $data;
    }

    // This function is being used in PrivacyAssessmentController
    // Do make changes carefully
    public function getRiskMappingResults ($dpia_id) {
        $dpia = Dpia::find($dpia_id);
        $sub_categories = [];
        $total = 0;
        $tags = ['severity' => 'risk_severity', 'likelihood' => 'risk_likelihood'];
        if ($dpia) {
            if($dpia->dpia_questions->count() > 0) {
                $questions = $dpia->dpia_questions()->with('finalRemark')->whereIn('tag', ['Severity', 'Likelihood'])->get();
//                return $questions;
                foreach ($questions as $question) {
                    if ($question->dpia_bar_reference !== null) {

                        $stored_question[strtolower($question->tag)][] = $question->dpia_bar_reference()->pluck('bar_value')[0];
                        $stored_question[strtolower($question->tag)][] = $question->finalRemark !== null ? $question->finalRemark()->pluck($tags[strtolower($question->tag)])[0] : null;

                        $total += 1;
                        if($total == 2) {
                            if(!array_key_exists($question->dpia_subcategory->name, $sub_categories)) {
                                $sub_categories[$question->dpia_subcategory->name] = $stored_question;
                                $stored_question = [];
                                $total = 0;
                            }
                        }
                    }
                }
            }
        }
        return $sub_categories;
    }

    public function getPreviousAnswers(Request $request) {
        $answers = [];
        if($request->ajax()) {

            if ($request->get('tag') !== 'Measures') {
                $questions = DpiaQuestion::where([
                    'dpia_id' => $request->get('dpia_id'),
                    'tag' => $request->get('tag')
                ])->get();
                if($questions->count() > 0) {
                    foreach ($questions as $question) {
                        if ($question->dpia_final_answer !== null && $question->dpia_final_answer->description !== '') {
                            $tags = explode(',', $question->dpia_final_answer->description);
                            foreach ($tags as $tag) {
                                if(!in_array($tag, $answers)) {
                                    array_push($answers, $tag);
                                }
                            }
                        }
                    }
                }
            } else {
                $sub_category_id = DpiaSubCategory::where(['name' => 'Planned or Existing Measures'])->pluck('id');
                $answers = DpiaQuestion::where([
                    'dpia_id' => $request->get('dpia_id'),
                    'dpia_sub_category_id' => $sub_category_id
                ])->pluck('description')->toArray();
            }


            $this->message = 'Data Found';
            $this->status_code = 200;
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;

            return response(['message' => $this->message], $this->status_code);
        }

        return response(['message' => $this->message, 'answers' => $answers], $this->status_code);
    }

    private function sendDpiaTaskShiftEmail($to_whom, Dpia $dpia, $is_forward) {
        $user = '';
        switch ($to_whom) {
            case 'editor':
                $user = DpiaStakeHolder::where('user_id', '=', $dpia->editor_id)->first();
                if ($user->user_type !== 'editor') {
                    $user->user_type = 'editor';
                }
                break;
            case 'reviewer':
                $user = DpiaStakeHolder::where('user_id', '=', $dpia->reviewer_id)->first();
                if ($user->user_type !== 'reviewer') {
                    $user->user_type = 'reviewer';
                }
                break;
            case 'dpo':
                $dpo = $dpia->dpia_dpo;
                $user = DpiaStakeHolder::where('id', '=', $dpo->stakeholder_id)->first();
                if ($user->user_type !== 'dpo') {
                    $user->user_type = 'dpo';
                }
                break;
            case 'concern person':
                $concern_person = $dpia->dpia_concern_person;
                $user = DpiaStakeHolder::where('id', '=', $concern_person->stakeholder_id)->first();
                if ($user->user_type !== 'concern person') {
                    $user->user_type = 'concern person';
                }
                break;
            case 'validator':
                $user = DpiaStakeHolder::where('user_id', '=', $dpia->validator_id)->first();
                if ($user->user_type !== 'validator') {
                    $user->user_type = 'validator';
                }
                break;
            case 'approved':
                // $this->sendEmailToAll($dpia);
                break;
            default:

        }
        if ($to_whom !== 'approved') {
            $owner = User::find($user->created_by_id);
            $user->owner_name = $owner['name'];
            Mail::to($user->contact_email)->bcc(config('app.hubspot_bcc'))->send(new DpiaTaskShiftMail($user, $dpia, $is_forward));
            //  $to = ['email' => $user->contact_email, 'name' => ''];
//         $template = ['user' => $user,
            // 'dpia' => $dpia,
            // 'is_forward' => $is_forward
            // ]
//         ];
//         sendEmailViaSendGrid($to, $template);
        }
    }

    private function sendEmailToAll(Dpia $dpia) {
        $data = [];
        $dpo = $dpia->dpia_dpo;
        $concern_person = $dpia->dpia_concern_person;

        $editor = DpiaStakeHolder::where(['user_id' => $dpia->editor_id])->select('name', 'user_id', 'contact_email', 'user_type')->first();
        $reviewer = DpiaStakeHolder::where(['user_id' => $dpia->reviewer_id])->select('name' ,'user_id', 'contact_email', 'user_type')->first();
        $validator = DpiaStakeHolder::where(['user_id' => $dpia->validator_id])->select('name' ,'user_id', 'contact_email', 'user_type')->first();
        $dpo = DpiaStakeHolder::where(['id' => $dpo->stakeholder_id])->select('name', 'user_id', 'contact_email', 'user_type')->first();

        if ($editor['user_type'] === 'owner' || $editor['user_type'] === 'multi role') { $editor['user_type'] = 'editor'; }
        if ($reviewer['user_type'] === 'owner' || $reviewer['user_type'] === 'multi role') { $reviewer['user_type'] = 'reviewer'; }
        if ($validator['user_type'] === 'owner' || $validator['user_type'] === 'multi role') { $validator['user_type'] = 'validator'; }
        if ($dpo['user_type'] === 'owner' || $dpo['user_type'] === 'multi role') { $dpo['user_type'] = 'dpo'; }

        $data[] = $editor;
        $data[] = $reviewer;
        $data[] = $validator;
        $data[] = $dpo;

        if ($concern_person !== null) {
            $concern_person = DpiaStakeHolder::where(['id' => $concern_person->stakeholder_id])->select('name', 'user_id', 'contact_email', 'user_type')->first();
            if ($concern_person['user_type'] === 'owner' || $concern_person['user_type'] === 'multi role') { $concern_person['user_type'] = 'concern person'; }
            $data[] = $concern_person;
        }
        foreach ($data as $user) {
            Mail::to($user['contact_email'])->bcc(config('app.hubspot_bcc'))->send(new DpiaApprovedMail($user, $dpia));
             //  $to = ['email' => $user->contact_email, 'name' => ''];
//         $template = ['user' => $user,
            // 'dpia' => $dpia,
            // ]
//         ];
//         sendEmailViaSendGrid($to, $template);
            
        }
    }

    public function getCategoryCompleteStatus ($dpia_id) {
        return DpiaCatsCompleteStatus::where([
            'dpia_id' => $dpia_id,
            'user_id' => auth()->id(),
            'status' => 'complete'
        ])->pluck('dpia_sub_category_id')->toArray();
    }

    public function getRiskOverviewResults ($dpia_id) {
//        $dpia = Dpia::find($dpia_id);
        $category = DpiaCategory::where('name', 'Risks')->first();
        $sub_categories = $category->sub_categories()->whereNotIn('name', ['Planned or Existing Measures', 'Risks Overview'])->get();

        if ($sub_categories->count() === 0) return [];


        $final_structure = [];
        foreach ($sub_categories as $sub_category) {
            $questions = $sub_category->questions()->where('dpia_id', $dpia_id)->get();
            $category = [];
            $category['name'] = $sub_category->name;
            $category['value'] = 5;
            $category['children'] = [];
            if ($questions->count() > 0) {
                foreach ($questions as $question) {

                    if (!in_array(strtolower($question->tag), ['severity', 'likelihood'])) {
                        $tagCollection = [];
                        $tagCollection['name'] = $question->tag;
                        $tagCollection['value'] = 3;
                        $tagCollection['children'] = [];
                        $children = [];
                        $array = explode(',', $question->dpia_final_answer->description ?? '');
                        foreach ($array as $item) {
                            $names = [];
                            $names['name'] = $item;
                            $names['value'] = 1;
                            $children[] = $names;
                        }
                        $tagCollection['children'] = $children;
                        $category['children'][] = $tagCollection;
                    }
                }
            }
            $final_structure[] = $category;
        }
        return $final_structure;
    }

    private function getPlannedKnowledgeBases() {
        return [
            [
                'name' => 'Encryption',
                'title' => 'Encryption',
                'description' => 'Means implemented for ensuring the confidentiality of data stored (in the database, in flat files, backups, etc.), as well as the procedure for managing encryption keys (creation, storage, change in the event of suspected cases of data compromise, etc.). Describe the encryption means employed for data flows (VPN, TLS, etc.) implemented in the processing.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Anonymisation',
                'title' => 'Anonymisation',
                'description' => 'Indicate here whether anonymisation mechanisms are implemented, which ones and for what purpose. Remember to clearly distinguish between anonymous and pseudonymous data.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Partitioning data',
                'title' => 'Partitioning data',
                'description' => 'Implementation of data partitioning helps to reduce the possibility that personal data can be correlated and that a breach of all personal data may occur.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Logical access control',
                'title' => 'Logical access control',
                'description' => 'Methods to define and attribute users profiles. Specify the authentication means implemented . Where applicable, specify the rules applicable to passwords (minimum length, required characters, validity duration, number of failed attempts before access to account is locked, etc.).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Traceability (logging)',
                'title' => 'Traceability (logging)',
                'description' => 'Policies that define traceability and log management.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Archiving',
                'title' => 'Archiving',
                'description' => 'Where applicable, describe here the processes of archive management (delivery, storage, consultation, etc.) under your responsibility. Specify the archiving roles (offices of origin, transferring agencies, etc.) and the archiving policy. State if data may fall within the scope of public archives.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Paper document security',
                'title' => 'Paper document security',
                'description' => 'Where paper documents containing data are used during the processing, indicate here how they are printed, stored, destroyed and exchanged.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Minimising the amount of personal data',
                'title' => 'Minimising the amount of personal data',
                'description' => 'The following methods could be used: filtering and removal, reducing sensitivity via conversion, reducing the identifiable nature of data, reducing data accumulation and restricting data access',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Operating security',
                'title' => 'Operating security',
                'description' => 'Policies implemented to reduce the possibility and the impact of risks on assets supporting personal data.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Clamping down on malicious software',
                'title' => 'Clamping down on malicious software',
                'description' => 'Controls implemented on workstations and servers to protect them from malicious software while accessing less secure networks.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Managing workstations',
                'title' => 'Managing workstations',
                'description' => 'Controls implemented on workstations (automatic locking, regular updates, configuration, physical security, etc.) to reduce the possibility to exploit software properties (operating systems, business applications etc.) to adversely affect personal data.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Website security',
                'title' => 'Website security',
                'description' => 'Implementation of ANSSI\'s recommendations for securing websites.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Backups',
                'title' => 'Backups',
                'description' => 'Policies and means implemented to ensure the availability and/or integrity of the personal data, while maintaining its confidentiality.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Maintenance',
                'title' => 'Maintenance',
                'description' => 'Policies describing how physical maintenance of hardware is managed, stating whether this is contracted out. Indicate whether the remote maintenance of apps is authorized, and according to what arrangements. Specify whether defective equipment is managed in a specific manner.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Processing contracts',
                'title' => 'Processing contracts',
                'description' => 'Only use subcontractors who are able to provide sufficient guarantees (in particular in terms of specialized knowledge, reliability and resources). Require service providers to communicate their information system security policy before signing a contract with them. Request and document the means (security audits, installation visits, etc.) used to ensure the effectiveness of the guarantees offered by the subcontractor in terms of data protection. These guarantees include: encryption of data according to its sensitivity or, at least, the existence of procedures guaranteeing that the service company does not have access to the data; encryption of data transmissions (e.g. HTTPS type connection, VPN, etc.); guarantees in terms of network protection, traceability (e.g. logs, audits, etc.); access rights management, authentication, etc. Sign a contract with the subcontractors, which defines the subject, the length and the purpose of the processing, as well as obligations of each party. Ensure that it contains, in particular, provisions targeting: their obligation in terms of confidentiality of the entrusted personal data; minimal standards in terms of user authentication; conditions of restitution of data and/or its destruction at end of the contract; incident management and notification rules. They should include notification of the data controller whenever a security breach or a security incident is discovered, which should happen as soon as possible when it concerns personal data.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Network security',
                'title' => 'Network security',
                'description' => 'Depending on the type of network on which the processing is carried out (isolated, private or Internet). Specify which firewall system, intrusion detection systems or other active or passive devices are in charge of ensuring network security.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Physical access control',
                'title' => 'Physical access control',
                'description' => 'Policies to ensure physical security (zoning, escorting of visitors, wearing of passes, locked doors and so on).Indicate whether there are warning procedures in place in the event of a break-in.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Monitoring network activity',
                'title' => 'Monitoring network activity',
                'description' => 'Monitor intrusion detection systems and intrusion prevention systems in order to analyze network (wired networks, Wi-Fi, radio waves, fiber optics, etc.) traffic in real time and detect any suspicious activity suggestive of a cyber attack scenario.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Hardware security',
                'title' => 'Hardware security',
                'description' => 'Indicate here the controls bearing on the physical security of servers and workstations (secure storage, security cables, confidentiality filters, secure erasure prior to scrapping, etc.).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Avoiding sources of risk',
                'title' => 'Avoiding sources of risk',
                'description' => 'Documentation on implantation area, which should not be subject to environmental disasters (flood zone, proximity to chemical industries, earthquake or volcanic zone, etc.).Specify if dangerous products are stored in the same area.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Protecting against non-human sources of risks',
                'title' => 'Protecting against non-human sources of risks',
                'description' => 'Policies describing the means of fire prevention, detection and fighting. Where applicable, indicate the means of preventing water damage. Also specify the means of power supply monitoring and relief.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Physical Security control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Organization',
                'title' => 'Organization',
                'description' => 'SSpecify whether a person is responsible for the enforcement of privacy laws and regulations. Specify whether there is a monitoring committee (or equivalent) responsible for the guidance and follow-up of actions concerning the protection of privacy.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Policy',
                'title' => 'Policy',
                'description' => 'Set out important aspects relating to data protection within a documentary base making up the data protection policy and in a form suited to each type of content (i.e. risks, key principles to be followed, target objectives, rules to be applied, etc.) and each communication target (i.e. users, IT department, policymakers, etc.).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Managing privacy risks',
                'title' => 'Managing privacy risks',
                'description' => 'Policy describing processes to control the risks that processing operations performed by the organization pose to data protection and the privacy of data subjects (i.e. building a map of the risks, etc.)',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Integrating privacy protection in projects',
                'title' => 'Integrating privacy protection in projects',
                'description' => 'Existence of a policy designed to integrate the protection of personal data in all new processing operations.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Managing personal data violations',
                'title' => 'Managing personal data violations',
                'description' => 'Existence of an operational organization that can detect and treat incidents that may affect the data subjects\' civil liberties and privacy.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Personnel management',
                'title' => 'Personnel management',
                'description' => 'Existence of a policy describing awareness-raising controls are carried out with regard to a new recruit and what controls are carried out when persons who have been accessing data leave their job.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Relations with third parties',
                'title' => 'Relations with third parties',
                'description' => 'Existence of a policy and processes reducing the risk that legitimate access to personal data by third parties may pose to the data subjects\' civil liberties and privacy.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Supervision',
                'title' => 'Supervision',
                'description' => 'Existence of a policy and processes to obtain an organization able to manage and control the protection of personal data held within it.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Organizational control',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Encryption',
                'title' => 'Encryption',
                'description' => 'A data security measure that involves making personal data unintelligible to anyone without authorized access (for e.g. symmetric or asymmetric encryption, use of public algorithms known to be strong, authentication certificate, etc.).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Anonymization',
                'title' => 'Anonymization',
                'description' => 'Process removing the identifiable characteristics from personal data. To assess the robustness of an anonymization process please refer to the WP29 guidelines.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Pseudonymization',
                'title' => 'Pseudonymization',
                'description' => 'Processing of personal data in such a manner that the personal data can no longer be attributed to a specific data subject without the use of additional information, provided that such additional information is kept separately and is subject to technical and organizational measures to ensure that the personal data is not attributed to an identified or identifiable natural person. Pseudonymization reduces the linkability of a dataset with the original identity of a data subject; as such, it is a useful security measure but not a method of anonymization.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Data partitioning',
                'title' => 'Data partitioning',
                'description' => 'Data organization methods that reduce the possibility that personal data can be correlated and that a breach of all personal data may occur. For instance, by identifying the personal data useful only to each business process and logically separating it.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Logical access controls',
                'title' => 'Logical access controls',
                'description' => 'Means implemented to limit the risks that unauthorized persons will access personal data electronically, it requires among other things: to manage users\' profiles by separating tasks and areas of responsibility (preferably in centralised fashion) to limit access to personal data exclusively to authorized users by applying need-to-know and least-privilege principles; to withdraw the rights of employees, contracting parties and other third parties when they are no longer authorized to access a premises, a resource or when their employment contract ends.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Password',
                'title' => 'Password',
                'description' => 'Passwords shall be composed of a minimum of eight characters; must be renewed if there is the least concern that they may have been compromised and, possibly, periodically (every six months or once a year) and must include a minimum of three of the four kinds of characters (capital letters, lower case letters, numerals and special characters); when a password is changed, the last five passwords may not be reused; the same password should not be used for different accesses; passwords should not be related to one\'s personal information (including name or date of birth.). Define a maximum number of attempts beyond which a warning is issued and authentication is blocked (temporarily or until it is manually unblocked).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Authentication',
                'title' => 'Authentication',
                'description' => 'Every person with legitimate access to personal data (employees, contracting parties and other third parties) should be identified by a unique identifier. Choose an authentication method to open sessions that is appropriate to the context, the risk level and the robustness expected. Recommendations: if the risks are not elevated, a password may be used; however, if the risks are higher, use a one-time password token but change the default activation password, or, when part of the password is sent by SMS, a card with a PIN code, an electronic certificate or any other form of strong authentication.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Surveillance',
                'title' => 'Surveillance',
                'description' => 'Set up a logging architecture to allow early detection of incidents involving personal data and to have information that can be used to analyze them or provide proof in connection with investigations.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Archiving',
                'title' => 'Archiving',
                'description' => 'Procedures preserving and managing the electronic archives containing the personal data intended to ensure their value (specifically, their legal value) throughout the entire period necessary (transfer, storage, migration, accessibility, elimination, archiving policy, protection of confidentiality, etc.).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Filtering and removal',
                'title' => 'Filtering and removal',
                'description' => 'When data is being imported, different types of metadata (such as EXIF data with an image file attached) can unintentionally be collected. Such metadata must be identified and eliminated if it is unnecessary for the purposes specified.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Reducing sensitivity via conversion',
                'title' => 'Reducing sensitivity via conversion',
                'description' => 'Once sensitive data has been received, as part of a series of general information or transmitted for statistical purposes only, this can be converted into a less sensitive form or pseudonymized. For example: if the system collects the IP address to determine the user\'s location for a statistical purpose, the IP address can be deleted once the city or district has been deduced - if the system receives video data from surveillance cameras, it can recognise people who are standing or moving in the scene and blur them - if the system is a smart meter, it can aggregate the use of energy over a certain period, without recording it in real time',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Project Management',
                'title' => 'Project Management',
                'description' => 'Measures taken to integrate the protection of personal data in all new processing operations (trusted names, guidelines, CNIL methodology for risk management or other internal methodology).',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Personal data breach',
                'title' => 'Personal data breach',
                'description' => 'Breach of security leading to the accidental or unlawful destruction, loss, alteration, unauthorized disclosure of, or access to, personal data transmitted, stored or otherwise processed.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Reducing the identifying nature of data',
                'title' => 'Reducing the identifying nature of data',
                'description' => 'The system can ensure that: the user can use a resource or service without the risk of disclosing his/her identity (anonymous data); the user can use a resource or service without the risk of disclosing his/her identity, but remain identifiable and responsible for this use (pseudonymous data); the user can make multiple uses of resources or services without the risk of these different uses being linked together (data cannot be correlated); the user can use a resource or service without the risk of others, third parties in particular, being able to observe that the resource or service is being used (non-observability). The choice of a method from the list above must be made on the basis of the threats identified. For some types of threat to privacy, pseudonymization will be more appropriate than anonymization (for example, if there is a traceability need). In addition, some threats to privacy will be addressed using a combination of methods.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Reducing data accumulation',
                'title' => 'Reducing data accumulation',
                'description' => 'The system can be organised into independent parts with separate access control functions. The data can also be divided between these independent sub-systems and controlled by each sub-system using different access control mechanisms. If a sub-system is compromised, the impacts on all of the data can thus be reduced.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
            [
                'name' => 'Restricting data access',
                'title' => 'Restricting data access',
                'description' => 'The system can limit data access according to the "need to know" principle. The system can separate the sensitive data and apply specific access control policies. The system can also encrypt sensitive data to protect their confidentiality during transmission and storage. Access to temporary cookies which are produced during the data processing must also be protected.',
                'is_importable' => 1,
                'dpia_knowledge_base' => [
                    'name' => 'Definition',
                    'title' => 'Principle',
                ],
                'rel_knowledge_base' => [
                    'name' => 'Logical security control',
                    'title' => 'Principle',
                ]
            ],
        ];
    }
}
