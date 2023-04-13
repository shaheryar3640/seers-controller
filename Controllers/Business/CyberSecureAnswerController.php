<?php

namespace App\Http\Controllers\Business;


use App\Http\Controllers\Controller;
use App\Mail\CyberSecureAdvanced;
use App\Mail\CyberSecureCertificateAdvanced;
use App\Mail\CyberSecureCertificateSimple;
use App\Mail\CyberSecureReport;
use App\Mail\CyberSecureSimple;
use App\Mail\ReportMail;
use App\Models\Setting;
use App\Models\Product;
use App\Models\CyberSecure;
use App\Models\CyberSecureAnswer;
use App\Models\CyberSecureHistory;
use App\Models\CyberSecureCertificate;
use App\Models\MembershipPlans;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use PDF;

class CyberSecureAnswerController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function __construct()
    {

    }

    public function index(){
        $cyber_secures = CyberSecure::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        return view('business.assessments-cyber-secures')->with(['cyber_secures'=>$cyber_secures]);
    }
    public function indexTest(){
        $cyber_secures = CyberSecure::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //dd($cyber_secures);
        return response()->json(['cyber_secures'=>$cyber_secures]);
    }
    public function show($slug)
    {
//        dd(hasProduct('assessment', 'cyber_secure'));
        if(!hasProduct('assessment', 'cyber_secure')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }
        
        $current_cyber_secure = CyberSecure::with('answer')->where(['slug' => $slug])->first();
        //dd($current_cyber_secure);
//        $eligible = 'yes';
//        if($current_cyber_secure->CyberSecureAssociation->count() > 0) {
//            foreach ($current_cyber_secure->CyberSecureAssociation as $association){
//                if($association->keyword != 'is_signup') {
//                    $asso_plan = MembershipPlans::where('id', $association->plan_id)->first();
//                    if (($association->plan_id == Auth::User()->membership_plan_id) && ($association->enabled == 0)) {
//                        $eligible = 'no';
//                        break;
//                    }elseif($association->enabled == 1){
//                        //var_dump( Auth::User()->MembershipPlans->sort_order.'--->'.$asso_plan->sort_order);
//
//                        if(Auth::User()->MembershipPlans->sort_order > $asso_plan->sort_order) {
//                            break;
//                        }
//                    }
//                }
//            }
//        }
//         if($eligible == 'no'){
//             //$activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
//
//             //return view('price-plan')->with(['activePlans'=>$activePlans]);
//             return redirect('/business/price-plan');
//         }
//        //dd($current_cyber_secure->answer);
////        if(count($current_cyber_secure) <= 0)
//        if(!$current_cyber_secure)
//            return view('404');
//        elseif($current_cyber_secure->answer != null){
//            if ($current_cyber_secure->answer->done == 1){
//                return redirect('/business/cyberSecure/report/'.$slug.'.html');
//            }
//        }
        //$cyber_secures = CyberSecure::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return response()->json(['cyber_secures' => $cyber_secures]);
        /*dd($current_cyber_secure);
        die('die');*/
        //dd('hello outter if',$cyber_secures);
        return view('business.cyber_secures.index')->with(['current_cyber_secure' => $current_cyber_secure]);
    }
    public function showResult($slug)
    {
        $current_cyber_secures = CyberSecure::with('answer')->where(['slug' => $slug])->first();
//        if(count($current_cyber_secures) <= 0)
        if(!$current_cyber_secures)
            return view('errors.404');
        $cyber_secures = CyberSecure::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return view('business.cyber_secures.certificate');
        return view('business.cyber_secures.report')->with(['cyber_secures' => $cyber_secures, 'current_cyber_secures' => $current_cyber_secures]);
    }
    public function saveResult(Request $request)
    {
        $data = json_encode($request->all());
        //dd($request->get('date'));
        $cyber_id = $request->get('cyber_id');
        $cyber_secures = CyberSecureAnswer::where(['cyber_id' => $request->get('cyber_id'),'user_id' => Auth::User()->id])->first();

        if($cyber_secures->result == "") {
            $cyber_secures->result = $data;
            $cyber_secures->save();
        }

        if ($request->get('sendEmailValue') == true) {
            $cyber_secures_question = CyberSecure::where(['id' => $request->get('cyber_id')])->first();
            return response()->json(['url' => route('business.cyber_readiness', ['slug' => $cyber_secures_question->slug])]);
        }

        if($request->get('retry') == true){
            $t_history = new CyberSecureHistory();
            $t_history->user_id = Auth::User()->id;
            $t_history->cyber_id = $cyber_id;
            $t_history->answers = json_encode($request->get('answers'));
            $t_history->result = $data;
            $t_history->previous_status = $request->get('compliant');
            $t_history->save();
            //dd()
            //$cyber_secures->delete();
            if($cyber_secures != null){
                CyberSecureAnswer::destroy($cyber_secures->id);
            }
            $cyber_secures_question = CyberSecure::where(['id' => $request->get('cyber_id')])->first();
            return response()->json(['url'=>route('business.cyber_readiness' ,['slug'=>$cyber_secures_question->slug])]);
        }
        /*if (($request->get('retry') == false) && ($request->get('generatePDF') == false)) {
            return response()->json(['url' => route('business.cyberSecure.downloadResult', ['id' => $cyber_id])]);
        }*/
        //return view('business.result')->with(['data' => $data]);
    }
    public static function emailReport($id, Request $request){

        $cyber_secures_question = CyberSecure::where(['id' => $id])->first();
        //dd($cyber_secures_question->slug);

        $cyber_secures = CyberSecureAnswer::where(['cyber_id' => $id,'user_id' => Auth::User()->id])->first();
        //dd($cyber_secures);
        $data = json_decode($cyber_secures->result);
        $data->date = $cyber_secures->updated_at;
        $user = $request->get('user');
        //dd($data->compliant);


        $pdf = PDF::loadView('business.cyber_secures.result', ['data'=>$data, 'user' => Auth::user()]);
        
        if($cyber_secures_question->slug == 'Cyber-Secure-Premium'){
            if($data->compliant == "100.00"){
                Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureCertificateSimple($data,$pdf,$user));

                  //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data'=> $data , 'user' => $user]

//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
                
            }else{
                Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureSimple($data,$pdf,$user));
                //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data'=> $data , 'user' => $user]

//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
            }
        }else if ($cyber_secures_question->slug == 'cyber-secure-advance'){
            if($data->compliant == "100.00"){
                Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureCertificateAdvanced($data,$pdf,$user));
                //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data'=> $data , 'user' => $user]

//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
            }else{
                Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureAdvanced($data,$pdf,$user));
                //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data'=> $data , 'user' => $user]

//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
            }
        }else{
            Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureReport($data,$pdf,$user));
            //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data'=> $data , 'user' => $user]

//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
        }
    }
    public function downloadPdf($id){
        
        
        $cyber_secures = CyberSecureAnswer::where(['cyber_id' => $id,'user_id' => Auth::User()->id])->first();

        $data = json_decode($cyber_secures->result);
        $data->date = $cyber_secures->updated_at;
        // dd($data);

        $pdf = PDF::loadView('business.cyber_secures.result', ['data'=>$data, 'user' => Auth::user()]);
       
        return $pdf->download('cyber_secures_Report.pdf');
    }
    public function getCertificatePdf($id){
        
        $type = 0;
        
        $cyber_secure = CyberSecure::where('id', $id)->first();
        // dd($cyber_secure);
        $cyber_secure_answers = CyberSecureAnswer::where(['cyber_id' => $id,'user_id' => Auth::User()->id])->first();
        // dd($cyber_secure_answers);
        $data = json_decode($cyber_secure_answers->result);
        // dd($data);
        $data->date = $cyber_secure_answers->updated_at;
        
        if($cyber_secure->slug == 'Cyber-Secure-Premium'){
            $type = 'premium';
        }else if($cyber_secure->slug == 'cyber-secure-advance'){
            $type = 'advance';
        }else{
            $type = 'sample';
        }
        
        $cyber_secure_certificate = CyberSecureCertificate::where(['cyber_id' => $id,'user_id' => Auth::User()->id, 'type' => $type ])->first();
        if($cyber_secure_certificate == null) {
            $cyber_secure_certificate = new CyberSecureCertificate();
            $cyber_secure_certificate->serial = rand(1000000000,9999999999);
            $cyber_secure_certificate->cyber_id = $id;
            $cyber_secure_certificate->user_id = Auth::User()->id;
            $cyber_secure_certificate->type = $type;
            $cyber_secure_certificate->org_name = Auth::User()->company;
            $cyber_secure_certificate->org_address = Auth::User()->address;
            $cyber_secure_certificate->issue_date = date('Y-m-d H:i:s', time());
            $cyber_secure_certificate->expiry_date = date("Y-m-d H:i:s", strtotime("+1 year", time()));
            $cyber_secure_certificate->save();
        }

        // dd($cyber_secure_certificate);

        //$pdf = PDF::loadView('business.cyber_secures.certificate', ['data'=>$data]);
        //return view('business.cyber_secures.certificate');

        $pdf = PDF::loadView('business.cyber_secures.certificate', ['data' => $cyber_secure_certificate]);
    
        return $pdf->stream('cyber_secures_Certificate.pdf');
    }
    public function showProgress()
    {
        $total_cyber_secures = CyberSecure::where('sort_order','>','0')->count();
        $done_answers = CyberSecureAnswer::where(['user_id'=>Auth::User()->id, 'done' => 1])->count();
        $progress =0;
        $progress =($done_answers / $total_cyber_secures) *100;
        $progress =(int)$progress;

        //var_dump('$progress',$progress);

        $user = User::find(Auth::User()->id);
        return response()->json(['progress' => $progress,'total_cyber_secures' => $total_cyber_secures,'done_answers' => $done_answers , 'user' => $user]);
    }
    public function saveAnswers(Request $request){
        // $test = $request->all();
        // var_dump('here');
        // var_dump($test);
        // die();
        
        $cyber_secures_answers = CyberSecureAnswer::firstOrCreate(['cyber_id' => $request->get('cyber_id'),'user_id' => Auth::User()->id]);
        $cyber_secures_answers->fill($request->all());

        $cyber_secures_answers->save();

        $done = $request->get('done');

        if($done == true)
            return response()->json(['cyber_secures_answers' => $cyber_secures_answers]);
    }
    public function routeCyberSecuresCertificate(){
        return view('business.cyber_secures.certificate');
    }
    public function routeGetAllCyberSecures(){
         $product = Product::where(['name' => 'assessment'])->first();
        $plan = null;
        if ($product) {
            $user = Auth::User();
            $u_product = $user->currentProduct($product->name);
            if ($u_product) {
                $plan = $u_product->plan->name;
            }
        }
        return response()->json([
            'all_cyber_secures'=> CyberSecure::where('sort_order','>','0')->orderBy('sort_order')->get(),
            'plan' => $plan]);
    }
    public function routegetCurrentCyberSecure($id){
         return response()->json(['current_cyberSecure'=>CyberSecure::where('id','=',$id)->first()]);
    }
    public function routegetAllCyberSecureAnswers(){
        return response()->json(['all_cyberSecure_answers'=>CyberSecureAnswer::where('sort_order','>','0')->orderBy('sort_order')->get(['id','name','icon','slug','icon_class','sort_order'])]);
    }
    public function routegetCyberSecureHistory($slug){
        return response()->json(['current_cyberSecure'=>CyberSecure::with('answer')->where(['slug' => $slug])->first()]);
    }

}