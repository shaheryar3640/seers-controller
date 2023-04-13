<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\DpiaCategory;
use App\DpiaSubCategory;

use App\Dpia;
use App\DpiaQuestion;
use App\DpiaAnswer;

use App\DpiaLogs;
use Auth;

class DpiaQuestionController extends Controller
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
        $dpia = Dpia::find($id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.dpias.question.create', compact(['dpia', 'dpiaCategory', 'dpiaSubCategory']));
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
        //$request->validate(Dpia::$rules,Dpia::$messages);
        $data = $request->all();

        //dd($data);
        $dpia = Dpia::find($data["dpia_id"]);

        $question = new DpiaQuestion();
        $question->dpia_category_id = $data["category_id"];
        $question->dpia_sub_category_id = $data["subcategory_id"];
        $question->dpia_id = $dpia->id;
        $question->description = $data["question"];
        if(isset($data['mandatory']) && $data['mandatory'] == true){
            $question->is_mandatory = 1;
        }else{
            $question->is_mandatory = 0;
        }
        $question->input_type = $data['input_type'] ?? 'textarea';
        $question->tag = $data['tag'] ?? null;
        $question->created_by_id = Auth::User()->id;
        $question->updated_by_id = Auth::User()->id;
        $question->enabled = 1;
        $question->save();

        $log = new DpiaLogs();
        $log->dpia_id = $question->id;
        $log->type = 'dpia question';
        $log->action = 'add dpia question';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($question);
        $log->save();

        $answer = new DpiaAnswer();
        $answer->description = $data["answer"];
        $answer->dpia_id = $dpia->id;
        $answer->dpia_question_id = $question->id;
        $answer->created_by_id = Auth::User()->id;
        $answer->updated_by_id = Auth::User()->id;
        $answer->enabled = 1;
        $answer->save();

        $log = new DpiaLogs();
        $log->dpia_id = $answer->id;
        $log->type = 'dpia answer';
        $log->action = 'add dpia answer';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($answer);
        $log->save();

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_management/questions/".$dpia->id);
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

        $question = DpiaQuestion::find($id);

        $dpia = Dpia::find($question->dpia_id);
        $dpiaCategory = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();

        return view('admin.dpia.dpias.question.edit', compact(['question','dpia', 'dpiaCategory', 'dpiaSubCategory']));
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
        $dpia = Dpia::find($data["dpia_id"]);

        $question = DpiaQuestion::find($id);
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
        $question->updated_by_id = Auth::User()->id;
        $question->enabled = 1;
        $question->save();

        $log = new DpiaLogs();
        $log->dpia_id = $question->id;
        $log->type = 'dpia question';
        $log->action = 'update dpia question';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($question);
        $log->save();

        $answer = DpiaAnswer::where(['dpia_question_id'=>$question->id])->first();
        if(isset($answer->id)){
            $answer->description = $data["answer"];
            $answer->updated_by_id = Auth::User()->id;
            $answer->enabled = 1;
            $answer->save();

            $log = new DpiaLogs();
            $log->dpia_id = $answer->id;
            $log->type = 'dpia answer';
            $log->action = 'update dpia answer';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($answer);
            $log->save();
        }

        return redirect("/admin/dpia_management/questions/".$dpia->id);
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

        $question = DpiaQuestion::find($id);

        $dpia_id = $question->dpia_id;

        if(isset($question->id)){
            $answer = DpiaAnswer::where(['dpia_question_id'=>$question->id])->first();

            if(isset($answer->id)){
                $log = new DpiaLogs();
                $log->dpia_id = $answer->id;
                $log->type = 'dpia answer';
                $log->action = 'delete dpia answer';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($answer);
                $log->save();

                DpiaAnswer::destroy($answer->id);
            }

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'dpia question';
            $log->action = 'delete dpia question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();

            DpiaQuestion::destroy($question->id);
        }

        return redirect("/admin/dpia_management/questions/".$dpia_id);
    }

    public function disable($id)
    {
        //dd('disable');

        $question = DpiaQuestion::find($id);

        if(isset($question->id)){
            $question->enabled = 0;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'dpia question';
            $log->action = 'disable dpia question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_management/questions/".$question->dpia_id);
    }

    public function enable($id)
    {
        //dd('enable');

        $question = DpiaQuestion::find($id);

        if(isset($question->id)){
            $question->enabled = 1;
            $question->updated_by_id = Auth::User()->id;
            $question->save();

            $log = new DpiaLogs();
            $log->dpia_id = $question->id;
            $log->type = 'dpia question';
            $log->action = 'enable dpia question';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($question);
            $log->save();
        }

        return redirect("/admin/dpia_management/questions/".$question->dpia_id);
    }
}
