<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use App\Models\User;

use Mail;
use Auth;

class AssessmentController extends Controller{

    function index(){
        return view('assessment.index');
    }

    function step1(Request $request){
        $question1 = $request->input('question1');
        if($question1 == 'step1'){
            return view('assessment.step1_1');
        }
        return view('assessment.stepa2');
    }

    function step1_1(Request $request){
        $question1_1 = $request->input('question1_1');
        if ($question1_1 == 'step1_1'){
            return view('assessment.final_eligible');
        }
        else
        {
            return view ('assessment.final_not_eligible');
        }
    }

    function stepa2(Request $request){
        $questiona2 = $request->input('questiona2');
        if( $questiona2 == 'stepa2'){
            return view('assessment.step1_1');
        } else {
            return view('assessment.stepa2_1');
        }
    }

    function stepa2_1(Request $request){
        $questiona2_1 = $request->input('questiona2_1');
        if( $questiona2_1 == 'stepa2_1'){
            return view('assessment.final_eligible');
        }
        else {
            return view('assessment.final_not_eligible');
        }

    }
    function assessment_readiness(){
        return view('assessment.step4');
    }
}
?>