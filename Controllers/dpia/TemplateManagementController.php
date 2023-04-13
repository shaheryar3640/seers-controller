<?php

namespace App\Http\Controllers\dpia;

use App\Models\DpiaCategory;
use App\Models\DpiaLogs;
use App\Models\DpiaSubCategory;
use App\Models\DpiaTemplateAnswer;
use App\Models\DpiaTemplateKnowledgeBase;
use App\Models\DpiaTemplateQuestion;
use App\Models\DpiaTemplates;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TemplateManagementController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getTemplates() {
        $message = '';
        $status_code = 0;
        $templates = null;
        if(request()->ajax()) {
            $templates = DpiaTemplates::where('Title', '!=', 'Compulsory for DPIA')
                ->where(['type' => 'General', 'admin_approval' => 1, 'enabled' => 1])
                ->orWhere('created_by_id', '=', Auth::User()->id)
                ->get();

            if ($templates->count() > 0) {
                $message = 'Found';
                $status_code = 200;
            } else {
                $message = 'Record not found';
                $status_code = 201;
            }
        } else {
            $message = 'Bad request';
            $status_code = 400;
        }

        return response([
            'message' => $message,
            'templates' => $templates,
            'template_categories' => $this->getCategoryStructure()
        ], $status_code);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $message = '';
        $status_code = 0;
        if($request->ajax()) {
            $data = $request->all();
            $templateDefault = DpiaTemplates::where(['admin_approval' => 1, 'enabled' => 1, 'is_mandatory' => 1, 'type' => 'General'])->first();

            if(isset($templateDefault->id)){
                $template = new DpiaTemplates;

                $template->title = $data['title'];
                $template->sector = $data['sector'];
                $template->type = 'Specific';
                $template->is_mandatory = 0;
                $template->created_by_id = Auth::User()->id;
                $template->updated_by_id = Auth::User()->id;
                $template->save();

                if(count($templateDefault->tmp_questions) > 0) {
                    foreach ($templateDefault->tmp_questions as $question) {
                        if($question->enabled == 1) {
                            $insQuestion = new DpiaTemplateQuestion();
                            $insQuestion->description = $question->description;
                            $insQuestion->dpia_templates_id = $template->id;
                            $insQuestion->dpia_category_id = $question->dpia_category_id;
                            $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                            $insQuestion->created_by_id = Auth::User()->id;
                            $insQuestion->updated_by_id = Auth::User()->id;
                            $insQuestion->type = 'Specific';
                            $insQuestion->is_mandatory = $question->is_mandatory;
                            $insQuestion->sort_order = $question->sort_order;
                            $insQuestion->admin_approval = 1;
                            $insQuestion->enabled = 1;
                            $insQuestion->save();

                            $insAnswer = new DpiaTemplateAnswer();
                            $insAnswer->description = $question->rel_answer->description;
                            $insAnswer->dpia_template_question_id = $insQuestion->id;
                            $insAnswer->created_by_id = Auth::User()->id;
                            $insAnswer->updated_by_id = Auth::User()->id;
                            $insAnswer->enabled = 1;
                            $insAnswer->save();

                            $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id'=>$templateDefault->id,'dpia_template_question_id'=>$question->id])->get();

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
                    }
                }
//                $log = new DpiaLogs();
//                $log->dpia_id = $template->id;
//                $log->type = 'template';
//                $log->action = 'add specific template';
//                $log->user_id = Auth::User()->id;
//                $log->json = json_encode($template);
//                $log->save();

                $message = 'Template created successfully';
                $status_code = 200;
            } else {
                $message = 'Bad Request';
                $status_code = 400;
            }
        } else {
            $message = 'Bad Request';
            $status_code = 400;
        }
        return response(['message' => $message], $status_code);
    }

    public function import(Request $request)
    {
        $message = '';
        $status_code = 0;
        if($request->ajax()) {
            $data = $request->all();
            //dd('importTemplate',$data);

            $templateDefault = DpiaTemplates::where(['id' => $data['id']])->first();

            if(isset($templateDefault->id)){
                $template = new DpiaTemplates;
                $template->title = $data['title'];
                $template->sector = $data['sector'];
                $template->type = 'Specific';
                $template->is_mandatory = 0;
                $template->created_by_id = Auth::User()->id;
                $template->updated_by_id = Auth::User()->id;
                $template->save();

                if(count($templateDefault->tmp_questions) > 0) {
                    foreach ($templateDefault->tmp_questions as $question) {
                        if($question->enabled == 1) {
                            $insQuestion = new DpiaTemplateQuestion();
                            $insQuestion->description = $question->description;
                            $insQuestion->dpia_templates_id = $template->id;
                            $insQuestion->dpia_category_id = $question->dpia_category_id;
                            $insQuestion->dpia_sub_category_id = $question->dpia_sub_category_id;
                            $insQuestion->created_by_id = Auth::User()->id;
                            $insQuestion->updated_by_id = Auth::User()->id;
                            $insQuestion->type = 'Specific';
                            $insQuestion->is_mandatory = $question->is_mandatory;
                            $insQuestion->sort_order = $question->sort_order;
                            $insQuestion->admin_approval = 1;
                            $insQuestion->enabled = 1;
                            $insQuestion->save();

                            $insAnswer = new DpiaTemplateAnswer();
                            $insAnswer->description = $question->rel_answer->description;
                            $insAnswer->dpia_template_question_id = $insQuestion->id;
                            $insAnswer->created_by_id = Auth::User()->id;
                            $insAnswer->updated_by_id = Auth::User()->id;
                            $insAnswer->enabled = 1;
                            $insAnswer->save();

                            $knowledgebases = DpiaTemplateKnowledgeBase::where(['dpia_template_id'=>$templateDefault->id,'dpia_template_question_id'=>$question->id])->get();

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
                    }
                }
//                $log = new DpiaLogs();
//                $log->dpia_id = $template->id;
//                $log->type = 'template';
//                $log->action = 'Import specific template';
//                $log->user_id = Auth::User()->id;
//                $log->json = json_encode($template);
//                $log->save();
                $message = 'Template created successfully';
                $status_code = 200;
            } else {
                $message = 'Something went wrong!';
                $status_code = 401;
            }
        } else {
            $message = 'Bad Request';
            $status_code = 400;
        }
        return response(['message' => $message], $status_code);
    }


    public function getTemplateStructure ($id) {
        $category_structure = $this->getCategoryStructure();
        return response()->json(['template_categories' => $category_structure]);
    }

    public function getCategoryStructure () {

        $categories = DpiaCategory::select('id', 'name', 'description')->where(['enabled' => 1])->whereNotIn('name',['Risks', 'Validation'])->get();

        if($categories->count() > 0) {
            $count = 1;
            foreach ($categories as $category) {
                $category->count = $count;
                if ($category->name === 'Risks') {
                    $sub_categories = $category->sub_categories()->select('id', 'name', 'description')->where('name', '=', 'Planned or Existing Measures')->get();
                } else {
                    $sub_categories = $category->sub_categories()->select('id', 'name', 'description')->get();
                }
                $category->sub_categories = $sub_categories;
                $count++;
            }
        }
        return $categories;
    }

    public function getTemplateQuestionStructure($template_id, $sub_category_id)
    {
        $message = '';
        $status_code = 0;
        $questions = $knowledge_bases = $sub_category = null;
        if (request()->ajax()) {
            $questions = $this->getQuestions($template_id, $sub_category_id);
            $knowledge_bases = $this->getKnowledgeBase($template_id, $sub_category_id);
            $sub_category = $this->getSubCategory($sub_category_id);

            $message = 'Data found';
            $status_code = 200;

        } else {
            $message = 'Bad Request';
            $status_code = 400;
        }

        return response([
            'message' => $message,
            'questions' => $questions,
            'knowledge_bases' => $knowledge_bases,
            'sub_category' => $sub_category
        ], $status_code);
    }

    public function getQuestions ($template_id, $sub_category_id) {
        return DpiaTemplateQuestion::where([
            'dpia_sub_category_id' => $sub_category_id,
            'dpia_templates_id' => $template_id,
            'enabled' => 1
        ])->get();
    }

    public function getKnowledgeBase ($template_id, $sub_category_id) {
        return DpiaTemplateKnowledgeBase::where([
            'dpia_sub_category_id' => $sub_category_id,
            'dpia_template_id' => $template_id,
            'enabled' => 1
        ])->get();
    }

    public function getSubCategory ($sub_category_id) {
        return DpiaSubCategory::with('dpia_category')->where('id', '=', $sub_category_id)->first();
    }

    public function saveQuestion(Request $request) {

        $question = $request->get('question');
        $template = DpiaTemplates::find($request->get("template_id"));
        $sub_category = DpiaSubCategory::find($request->get("sub_category_id"));
        $new_question = DpiaTemplateQuestion::firstOrCreate([
            // 'id' => $question['id'],
            'dpia_sub_category_id' => $sub_category->id,
            'dpia_templates_id' => $template->id,
        ]);

        $new_question->dpia_category_id = $sub_category->dpia_category->id;
        $new_question->dpia_sub_category_id = $sub_category->id;
        $new_question->dpia_templates_id = $template->id;
        $new_question->description = $question["description"] ?? null;
        $new_question->is_mandatory = 0;
        $new_question->type = "Specific";
        $new_question->created_by_id = Auth::User()->id;
        $new_question->updated_by_id = Auth::User()->id;
        $new_question->admin_approval = 1;
        $new_question->sort_order = 0;
        $new_question->enabled = 1;
        $new_question->save();

        $answer = DpiaTemplateAnswer::firstOrCreate([
            'dpia_template_question_id' => $new_question->id
        ]);

        $answer->description = $question['rel_answer']['description'];
        $answer->updated_description = $question['rel_answer']['updated_description'] ?? null;
        $answer->dpia_template_question_id = $new_question->id;
        $answer->created_by_id = Auth::User()->id;
        $answer->updated_by_id = Auth::User()->id;
        $answer->enabled = 1;
        $answer->save();

        return response([
            'message' => 'Question saved successfully!',
            'questions' => $this->getQuestions($template->id, $sub_category->id)
        ], 200);
    }

    public function deleteQuestion($id) {
        $question = DpiaTemplateQuestion::find($id);
        $template_id = 0;
        $sub_category_id = 0;
        if(!is_null($question)) {
            $template_id = $question->dpia_templates_id;
            $sub_category_id = $question->dpia_sub_category_id;
            if ($question->rel_answer) {
                $question->rel_answer->delete();
            }
            $question->delete();

            return response([
                'message' => 'Question deleted successfully!',
                'questions' => $this->getQuestions($template_id, $sub_category_id)
            ], 200);
        }

        return response([
            'message' => 'Something went wrong',
            'questions' => $this->getQuestions($template_id, $sub_category_id)
        ], 400);
    }

    public function destroy($id)
    {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $template = DpiaTemplates::find($id);
        if ($template !== null) {
            if ($template->tem_questions && $template->tem_questions->count() > 0) {
                foreach ($template->tem_questions as $question) {

                    if ($question->rel_answer !== null) {
                        $question->rel_answer->delete();
                    }

                    if ($question->rel_bar_reference !== null) {
                        $question->rel_bar_reference->delete();
                    }
                    $question->delete();
                }
            }

            $knowledge_bases = DpiaTemplateKnowledgeBase::where(['dpia_template_id' => $template->id])->get();

            if ($knowledge_bases && $knowledge_bases->count() > 0) {
                foreach ($knowledge_bases as $knowledge_base) {
                    $knowledge_base->delete();
                }
            }

            $template->delete();
            return response(['message' => 'Template deleted successfully'], 200);
        }
    }

    public function duplicateTemplate ($id) {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $template = DpiaTemplates::find($id);

        $duplicated = DpiaTemplates::create([
            'title' => '(Copy) ' . $template->title,
            'sector' => '(Copy) ' . $template->sector,
            'type' => 'Specific',
            'is_mandatory' => 0,
            'created_by_id' => Auth::User()->id,
            'updated_by_id' => Auth::User()->id,
        ]);

        if ($template->tmp_questions->count() > 0) {
            foreach ($template->tmp_questions as $question) {
                if ($question->enabled === 1) {
                    $new_question = new DpiaTemplateQuestion;
                    $new_question->description = $question->description;
                    $new_question->dpia_templates_id = $duplicated->id;
                    $new_question->dpia_category_id = $question->dpia_category_id;
                    $new_question->dpia_sub_category_id = $question->dpia_sub_category_id;
                    $new_question->created_by_id = Auth::User()->id;
                    $new_question->updated_by_id = Auth::User()->id;
                    $new_question->type = 'Specific';
                    $new_question->is_mandatory = $question->is_mandatory;
                    $new_question->sort_order = $question->sort_order;
                    $new_question->admin_approval = 1;
                    $new_question->enabled = 1;
                    $new_question->save();

                    $new_answer = new DpiaTemplateAnswer;
                    $new_answer->description = $question->rel_answer->description;
                    $new_answer->dpia_template_question_id = $new_question->id;
                    $new_answer->created_by_id = Auth::User()->id;
                    $new_answer->updated_by_id = Auth::User()->id;
                    $new_answer->enabled = 1;
                    $new_answer->save();

                    $knowledge_bases = DpiaTemplateKnowledgeBase::where([
                        'dpia_template_id' => $duplicated->id,
                        'dpia_template_question_id' => $question->id
                    ])->get();

                    if ($knowledge_bases->count() > 0) {
                        foreach ($knowledge_bases as $knowledge_base) {
                            if (isset($knowledge_base->id)) {
                                $new_kb = new DpiaTemplateKnowledgeBase;
                                $new_kb->title = $knowledge_base->title;
                                $new_kb->description = $knowledge_base->description;
                                $new_kb->dpia_template_id = $duplicated->id;
                                $new_kb->dpia_knowledge_base_cat_id = $knowledge_base->dpia_knowledge_base_cat_id;
                                $new_kb->dpia_sub_category_id = $knowledge_base->dpia_sub_category_id;
                                $new_kb->dpia_template_question_id = $new_question->id;
                                $new_kb->created_by_id = Auth::User()->id;
                                $new_kb->updated_by_id = Auth::User()->id;
                                $new_kb->is_importable = $knowledge_base->is_importable;
                                $new_kb->sort_order = $knowledge_base->sort_order;
                                $new_kb->enabled = 1;
                                $new_kb->save();
                            }
                        }
                    }
                }
            }
        }

        return response(['message' => 'Template duplicated successfully'], 200);
    }

    public function update(Request $request)
    {
        if(!$request->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $template = DpiaTemplates::find($request->get('id'));
        $template->title = $request->get('title');
        $template->sector = $request->get('sector');
        $template->save();

        return response(['message' => 'Template updated successfully'], 200);
    }
}
