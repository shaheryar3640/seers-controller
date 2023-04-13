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

class AssessmenthowController extends Controller{

    function assessmenthow (){
        return view('assessment_how');
    }

}
?>