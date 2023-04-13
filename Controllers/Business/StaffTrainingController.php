<?php

namespace App\Http\Controllers\Business;


use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\StaffTraining;
use App\Mail\EtrainingStaffMail;
use Auth;
use PDF;
use App\Models\Product;

class StaffTrainingController extends Controller
{

    private $urls = [
        'urls' => [
            ['url' => 'gdpr-assessment-1', 'visited' => false],
            ['url' => 'gdpr-assessment-2', 'visited' => false],
            ['url' => 'gdpr-assessment-3', 'visited' => false],
            ['url' => 'gdpr-assessment-4', 'visited' => false],
            ['url' => 'gdpr-assessment-5', 'visited' => false],
            ['url' => 'gdpr-assessment-6', 'visited' => false],
            ['url' => 'gdpr-assessment-7', 'visited' => false],
            ['url' => 'gdpr-assessment-8', 'visited' => false],
            ['url' => 'gdpr-assessment-9', 'visited' => false],
            ['url' => 'gdpr-assessment-10', 'visited' => false],
            ['url' => 'final_result', 'visited' => false],
        ]
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        if(request()->route() != null && request()->route()->getName() !== 'member.verify' && request()->route()->getName() !== 'guest-profile') {
            $this->middleware(function ($request, $next) {
                if (auth()->user()->is_new == 1) {
                    $this->current_product = Auth::user()->currentProduct('gdpr_training');
                }
                return $next($request);
            });
        }
    }
    public function index()
    {

        if (!hasProduct('gdpr_training')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }

        $product = Product::where('display_name', 'GDPR Staff Training')->first();
        $userProduct = null;
        if ($product) {
            $employees = StaffTraining::where('user_id', Auth::User()->id)->get();
            $userProduct = Auth::user()->currentProduct($product->name);
        }

        return view('business.staff-training', compact('userProduct'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //        $validator = Validator::make($request->all(), [
        ////            'email' => 'required|unique:staff_trainings,email|unique:users,email|max:255',
        //            'email' => 'required|unique:staff_trainings,email|max:255',
        //        ]);
        //
        //        if ($validator->fails()) {
        //            return response(['message' => 'Email already exists'], 401);
        //        } else {

        $auth_user = auth()->user()->email;
        if (isset($auth_user) && $auth_user == $request->get('email')) {
            return response(['message' => "You can't add yourself"], 401);
        }
        // check if request email is already in use staff training
        $staff_training = StaffTraining::where('email', $request->get('email'))->first();
        if ($staff_training) {
            return response(['message' => 'Email already exists'], 401);
        }
        $token = str_replace('/', '$hS@ls', Hash::make($request->get('email')));
        $actual_token = str_replace('/', '$hS@ls', Hash::make($request->get('email') . 'npm-run-watch'));
        //            $_link = 'https://seersco.com/staff-training/' . $token;
        $_link = route('member.verify', ['token' => $token]);
        $member = StaffTraining::create([
            'email' => $request->get('email'),
            'token' => $token,
            'actual_token' => $actual_token,
            'user_id' => Auth::User()->id
        ]);
        $member->questions = json_encode($this->urls);
        $member->save();
        if ($member != null) {
            $to = ['email' => $request->get('email'), 'name' => ''];
            $template = [
                'id' => config('sendgridtemplateid.GDPR-Training-Child-Invited'),
                'data' => ['gdpr_invite' => $_link]
            ];
            sendEmailViaSendGrid($to, $template);
            // Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new EtrainingStaffMail($_link));
            return response(['message' => 'Email Added Successfully'], 200);
        } else {
            return response(['message' => 'Something went wrong'], 400);
        }
        //        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $max_limit = 0;
        $members = StaffTraining::where('user_id', Auth::User()->id)->paginate(10);
        $product = auth()->user()->products()->where('name', '=', 'gdpr_training')->first();
        if ($product !== null) {
            $max_limit = $product->plan->features()->where('name', '=', 'no_of_employees')->pluck('value');
        }

        return response([
            'members' => $members,
            'max_limit' => $max_limit,
            'country' => null,
            'user_type' => Auth::User()->type,
        ], 200);
    }

    public function exportPdf(Request $request) {
        
        $members = StaffTraining::where('user_id', Auth::User()->id)->get();
       
        $pdf = PDF::loadView('business.staff-members-pdf', ['data' => $members])->setPaper('a4', 'landscape');
        // dd($pdf);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->download('StaffMembers.pdf');
    }

    public function exportGdprSample(){
        $path = storage_path("download").'/'.'gdprsampleupload.csv';
        return response()->download($path, 'gdprsampleupload.csv');
        // Storage::download('gdprsampleupload.csv');
    }
 
    public function addBulkStaffmembers(Request $request){
        //  dd($request->all());
        $header = ["email"];
        $filename = $request->file;
        $delimiter = ",";
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {    
                array_push($data, $row[0]);
            }
            fclose($handle);
        }
        $data = array_filter(array_unique($data), function($value) { return !is_null($value) && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL); });
        $exists = StaffTraining::select('email')->whereIn('email',$data)->get();
        $exists_emails = $exists->pluck('email')->toArray();
        if(count($exists_emails) > 0){
            return response(['message' => 'Email already exists','emails'=>$exists_emails], 201);
        }else if(($request->total_members + count($data)) > $request->member_limit){
            return response(['message' => 'You can not add more than '.$request->member_limit.' members'], 202);
        }else{
            foreach($data as $email){
                $token = str_replace('/', '$hS@ls', Hash::make($email));
                $actual_token = str_replace('/', '$hS@ls', Hash::make($email . 'npm-run-watch'));
                $_link = route('member.verify', ['token' => $token]);
                $member = StaffTraining::create([
                    'email' => $email,
                    'token' => $token,
                    'actual_token' => $actual_token,
                    'user_id' => Auth::User()->id
                ]);
                $member->questions = json_encode($this->urls);
                $member->save();
                if ($member != null) {
                    $to = ['email' => $email, 'name' => ''];
                    $template = [
                        'id' => config('sendgridtemplateid.GDPR-Training-Child-Invited'),
                        'data' => ['gdpr_invite' => $_link]
                    ];
                    sendEmailViaSendGrid($to, $template);
                }
            }
            return response(['message' => 'Staff members added successfully'], 200);
        }
    }

    public function getCountry()
    {

        if (!request()->ajax()) {
            return response(['Bad Request'], 400);
        }
        $ip = request()->ip();
        try {
            $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
            $country_code = \DB::connection('mysql2')->select($query);
            $country_code = $country_code && $country_code[0]->code != '-' ? $country_code : null;
            return $country_code;
        } catch (\Excecption $e) {
            return null;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $token
     * @return \Illuminate\Http\Response
     */
    public function varify($token)
    {
        $employee = StaffTraining::where('token', $token)->first();
        //         dd($employee);
        if ($employee != NULL) {
            session()->put('data', [
                'staff_email'   => $employee->email,
                // 'current_url'   => $employee->current_url,
                // 'previous_url'  => $employee->name ? $employee->previous_url : '/',
                'token'     => $employee->token,
                // 'score'         => $employee->score ? $employee->score : 0,
                // 'direction'     => true
            ]);

            if ($employee->name) {
                return redirect('gdpr-training' . $employee->current_url);
            }
            return redirect('request-login');
        } else {
            return view('errors.404');
        }
    }

    public function guestProfile(Request $request)
    {
        $employee = StaffTraining::where(['email' => $request->get('email'), 'token' => session()->get('data')['token']])->first();
        if ($employee != NULL) {
            $employee->name = $request->get('fname') . ' ' . $request->get('lname');
            $employee->current_url = ($employee->current_url == '') ? '/' : $employee->current_url;
            $employee->status = ($employee->current_url) ? 'Reached' : 'Pending';
            $employee->save();
            return redirect('gdpr-training' . $employee->current_url);
        }
        return view('errors.404');
    }

    public function resendEmail($id)
    {
        $employee =  StaffTraining::find($id);
        if ($employee != NULL) {
            $token = str_replace('/', '$hS@ls', Hash::make($employee->email));
            $actual_token = str_replace('/', '$hS@ls', Hash::make($employee->email . 'npm-run-watch'));
            $_link = route('member.verify', ['token' => $token]);
            $employee->token = $token;
            $employee->actual_token = $actual_token;
            $employee->questions = json_encode($this->urls);
            $employee->save();
            $to = ['email' => $employee->email, 'name' => ''];
            $template = [
                'id' => config('sendgridtemplateid.GDPR-Training-Child-Invited'),
                'data' => ['gdpr_invite' => $_link]
            ];
            sendEmailViaSendGrid($to, $template);
            // Mail::to($employee->email)->bcc(config('app.hubspot_bcc'))->send(new EtrainingStaffMail($_link));
            return response()->json([
                'message' => 'We\'ve resend a link to \'' . $employee->email . '\''
            ], 200);
        }
    }

    public function deleteEmployee($id)
    {
        if ($id <= 0) {
            return response(['data' => false, 'message' => 'Please give a valid employee', 'code' => 403], 403);
        }

        $employee =  StaffTraining::find($id);
        if ($employee) {
            $employee->delete();
            return response(['data' => true, 'message' => 'Employee removed successfully', 'code' => 200], 200);
        } else {
            return response(['data' => false, 'message' => 'Employee could not found!', 'code' => 403], 403);
        }
    }
}
