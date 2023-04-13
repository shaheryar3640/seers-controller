<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingAdvisor;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\WelcomeAdvisor;
use App\Mail\BookAdviser;
use App\Mail\BookEuRepresentative;
use App\Mail\WelcomeMail;
use App\Mail\ContactClient;
use Illuminate\Support\Facades\Auth;

class BookingAdvisorController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    public $redirectTo = '/dashboard';
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    // public function show(){

    //     // if((Auth::check()) && (Auth::User()->user_type != 'Business')){
    //     //     return back();
    //     // }
    //     return view('booking_advisor');

    // }
    public function show(){

        // if((Auth::check()) && (Auth::User()->user_type != 'Business')){
        //     return back();
        // }
        return view('booking_advisor');

    }

    public function dpo_request_save(Request $request){
        $data = $request->all();
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$request->input('g-recaptcha-response'));
        $responseData = json_decode($verifyResponse);

        if($responseData->success==true) {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string',
            'subject' => 'required|string|max:255',
            'msg' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors'=>$validator->errors(),
                'message'=>'Please fill the required fields'],400);
        }

        /* Check if the request is coming from /book-eu-representative */
        if($data['eu_rep'] == true) {
            /* If yes, then use the EU rep mailable */
            session()->put('eu',true);
            Mail::send(new BookEuRepresentative($data));
            //     $to = ['email' => $data->email, 'name' => '$data->fname'];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data ]
        // ];
        // sendEmailViaSendGrid($to, $template);
        } else {
            /* If not, then use the book adviser mailable */
            session()->put('dpo',true);
            Mail::send(new BookAdviser($data));
            //     $to = ['email' => $data->email, 'name' => '$data->fname'];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data ]
        // ];
        // sendEmailViaSendGrid($to, $template);
        }
        Mail::send(new ContactClient($data));
        //     $to = ['email' => $data->email, 'name' => '$data->fname'];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data ]
        // ];
        // sendEmailViaSendGrid($to, $template);
        return redirect('/');
        }
        else{
            return response()->json(['message'=>'Error verifying reCAPTCHA, please try again.'],401);
        }

        
    }
}
