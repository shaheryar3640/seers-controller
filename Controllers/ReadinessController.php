<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use App\User;

use Mail;
use Auth;

class ReadinessController extends Controller{

    function index(){
        return view('readiness.index');
    }

    function stepr1(Request $request){
        $question1 = $request->input('question1');
        if($question1 == 'q1'){
            return view('readiness.stepr2');
        }
        return view('readiness.poorreadiness');
    }

    function stepr2(Request $request){
        $question2 = $request->input('question2');
        if($question2 == 'step2'){
            return view('readiness.step2');
        }
        else {
            return view('readiness.poorreadiness');
        }
    }


    function assessment_readiness(){
        return view('assessment.step4');
    }
}
?>