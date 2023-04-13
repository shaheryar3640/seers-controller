<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedBackMail;
use Auth;

class FeedBackController extends Controller
{
    public function index(Request $request)
    {
        
        $data = $request->all();
        $user = Auth::User();

    	Mail::send(new FeedBackMail($data,$user));
        //     $to = ['email' => 'support@mail.consents.dev', 'name' => ''];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data,'client_user'=>$user ]
        // ];
        // sendEmailViaSendGrid($to, $template);
    	return response()->json(['message' => 'Feedback send successfully']);
    }
}
