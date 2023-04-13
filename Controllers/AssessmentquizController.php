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

class AssessmentquizController extends Controller{

    function assessmentquiz (){
        return view('assessment_quiz');
    }

}
?>