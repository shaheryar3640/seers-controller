<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use App\Mail\NewsGuideMail;
use App\Mail\ContactClient;
use App\Mail\ScholarshipMail;
use App\Mail\ScholarShipStudent;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Auth;
use Crypt;
use Illuminate\Support\Facades\Validator;
use URL;

class PageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($seo_folder = null, $seo_url)
    {
        $page = Page::where(['seo_url' => $seo_url])->first();
        //dd($page);
        if ($page && $page->count() > 0)
            return view('pages')->with(['page' => $page]);

        return view('errors.404');
    }

    public function showPage($seo_url)
    {
        $page = Page::where(['seo_url' => $seo_url])->first();
        if ($page) {
            $pageurl = $page->seo_url;
            $pages = [
                'regulations',
                'terms-and-conditions',
                'privacy-policy',
            ];
            if (in_array($pageurl, $pages)) {
                return view('pages')->with(['page' => $page]);
            } else {
                return view('oldpages')->with(['page' => $page]);
            }
        } else {
            return view('errors.404');
        }     
       
    }


    public function show_page($name)
    {
        $pageName = preg_replace('/-+/', ' ', $name);

        $page = DB::table('pages')
            ->where('page_title', '=', ucfirst($pageName))
            ->get();

        if (count($page))
            return view('old.pages')->with(['page' => $page]);

        return view('errors.404');
    }
    // public function submitContactForm(Request $request)
    // {
    //     $data = $request->all();
    //    // dd($data);
    //     $name = $request['fname'];


    //     $secret = config('app.RECAPTCHA_SECRET_KEY');
    //     $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$request->input('g-recaptcha-response'));
    //     $responseData = json_decode($verifyResponse);
    //    //return response()->json(['message'=>json_decode($verifyResponse),'captchainput'=>$request->input('g-recaptcha-response'),'response'=>$responseData->success,'fname'=>$request['fname'],'data'=> $data],401);

    //     if($responseData->success==true) {
    //         Mail::send(new ContactMail($data));
    //         Mail::send(new ContactClient($data));
    //         //Mail::to('muhammad.zubair@consents.dev')->bcc(config('app.hubspot_bcc'))->send(new ContactMail($data));

    //         // return response()->json(['message'=>'Thank you for your enquiry. It has been forwarded to the relevant department and will be dealt with as soon as possible.'],200);
    //         return response()->json(['message'=>''],200);
    //         //return redirect()->back()->with('message', 'Thanks for sending us a message. We\'ll be in contact shortly');
    //     }else{
    //         return response()->json(['message'=>'Error verifying reCAPTCHA, please try again.'],401);
    //     }

    // }
    public function submitContactForm(Request $request)
    {
        $data = $request->all();
        // dd($data);
        // $name = clean($request['fname']);


        $secret = config('app.RECAPTCHA_SECRET_KEY');
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $request->input('g-recaptcha-response'));
        $responseData = json_decode($verifyResponse);
        //return response()->json(['message'=>json_decode($verifyResponse),'captchainput'=>$request->input('g-recaptcha-response'),'response'=>$responseData->success,'fname'=>$request['fname'],'data'=> $data],401);

        if ($responseData->success == true) {
            Mail::send(new ContactMail($data));

        //     $data['fname'] = clean($data['fname']);
        // $data['company'] = clean($data['company']);
        // $data['email'] = $data['email'];
        // $data['phone'] = clean($data['phone']);
        // $data['msg'] = clean($data['msg']);
        // $data['other'] = clean($data['other']);
        // $data['subject'] = clean($data['subject']);

        // $admin = User::where(['admin'=>'1'])->select('id','email')->first();
            //     $to = ['email' => $admin->email, 'name' => ''];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data,'show_greetings'=>true ]
        // ];
        // sendEmailViaSendGrid($to, $template);

            Mail::send(new ContactClient($data));
                 $to = ['email' => $data['email'], 'name' => $data['fname']];
        $template = [
            'id' => config('sendgridtemplateid.Contact-Us'), 
            'data' => [ "first_name" => $data['fname'] ]
        ];
        sendEmailViaSendGrid($to, $template);



            //Mail::to('muhammad.zubair@consents.dev')->bcc(config('app.hubspot_bcc'))->send(new ContactMail($data));

            // return response()->json(['message'=>'Thank you for your enquiry. It has been forwarded to the relevant department and will be dealt with as soon as possible.'],200);
            return response()->json(['message' => ''], 200);
            //return redirect()->back()->with('message', 'Thanks for sending us a message. We\'ll be in contact shortly');
        } else {
            return response()->json(['message' => 'Error verifying reCAPTCHA, please try again.'], 401);
        }
    }

    public function submitScholorShipForm(Request $request)
    {
        $data = $request->all();
        // dd($data);
        // $name = clean($request['fname']);


        $secret = config('app.RECAPTCHA_SECRET_KEY');
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $request->input('g-recaptcha-response'));
        $responseData = json_decode($verifyResponse);
        //return response()->json(['message'=>json_decode($verifyResponse),'captchainput'=>$request->input('g-recaptcha-response'),'response'=>$responseData->success,'fname'=>$request['fname'],'data'=> $data],401);

        if ($responseData->success == true) {
            Mail::send(new ScholarshipMail($data));
        // $data['fname'] = clean($data['fname']);
        // $data['institute'] = clean($data['institute']);
        // $data['email'] = $data['email'];
        // $data['phone'] = clean($data['phone']);
        // $data['msg'] = clean($data['msg']);
        // $data['other'] = clean($data['other']);
        // $data['subject'] = clean($data['subject']);

        // $admin = User::where(['admin'=>'1'])->select('id','email')->first();
                  //     $to = ['email' => $admin->email, 'name' => ''];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data,'show_greetings'=>true ]
        // ];
        // sendEmailViaSendGrid($to, $template);

            Mail::send(new ScholarShipStudent($data));
            //     $to = ['email' => $data['email'], 'name' => $data['fname']];
        // $template = [
        //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
        //     'data' => [ "data" => $data]
        // ];
        // sendEmailViaSendGrid($to, $template);

            //Mail::to('muhammad.zubair@consents.dev')->bcc(config('app.hubspot_bcc'))->send(new ContactMail($data));

            // return response()->json(['message'=>'Thank you for your enquiry. It has been forwarded to the relevant department and will be dealt with as soon as possible.'],200);
            return response()->json(['message' => ''], 200);
            //return redirect()->back()->with('message', 'Thanks for sending us a message. We\'ll be in contact shortly');
        } else {
            return response()->json(['message' => 'Error verifying reCAPTCHA, please try again.'], 401);
        }
    }

    public function newsForm(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'privacy_policy' => 'required',
            'terms_conditions' => 'required',
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'privacy_policy.required' => 'Privacy policy is required',
            'terms_conditions.required' => 'Terms and Conditions are required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => trans('guest.news_update')], 400);
        } else {
            // dd($data);
            $user = User::where('email', $request->get('email'))->get();
            if (!isset($user[0]->email)) {
                $user = new User();
                $user->fname = $request->get('name');
                $user->lname = '';
                $user->email = $request->get('email');
                $user->company = '';
                $user->phone = '';
                $user->job_role = '';
                $user->address = '';
                $user->admin = 3;
                $user->password = '';
                $user->save();
            }
            Mail::send(new NewsGuideMail($data));
            //     $to = ['email' => $data->email, 'name' => $data->fname];
            // $template = [
            //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
            //     'data' => [ "user" => $data]
            // ];
            // sendEmailViaSendGrid($to, $template);

            //Mail::to('muhammadahsan568@gmail.com')->bcc(config('app.hubspot_bcc'))->send(new ContactMail($data));
            return response()->json(['message' => 'Thank you'], 200);
        }
    }

    public function generalPages($name)
    {
        dd($name);
        return view($name);
    }
}
