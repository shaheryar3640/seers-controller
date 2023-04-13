<?php

namespace App\Http\Controllers\Business;


use App\Http\Controllers\Controller;
use App\Mail\ReportMail;
use App\Models\Setting;
use App\Models\Toolkit;
use App\Models\ToolkitAnswer;
use App\Models\ToolkitHistory;
use App\Models\MembershipPlans;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use PDF;

class ToolkitsAnswerController extends Controller
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
        $toolkits = Toolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        return view('business.assessments-toolkits')->with(['toolkits'=>$toolkits]);
    }
    public function indexTest(){
        $toolkits = Toolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        return response()->json(['toolkits'=>$toolkits]);
    }
    public function show($slug)
    {

        if(!hasProduct('assessment', 'gdpr_audit')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }
        
        $current_toolkit = Toolkit::with('answer')->where(['slug' => $slug])->first();

        $eligible = 'yes';
        if($current_toolkit->ToolkitAssociation->count() > 0) {
            foreach ($current_toolkit->ToolkitAssociation as $association){
                if($association->keyword != 'is_signup') {
                    $asso_plan = MembershipPlans::where('id', $association->plan_id)->first();

                    //dd($asso_plan->sort_order);

                    if (($association->plan_id == Auth::User()->membership_plan_id) && ($association->enabled == 0)) {
                        $eligible = 'no';
                        break;
                    }elseif($association->enabled == 1){

                        //var_dump( Auth::User()->MembershipPlans->sort_order.'--->'.$asso_plan->sort_order);

                        if(Auth::User()->MembershipPlans->sort_order > $asso_plan->sort_order) {
                            break;
                        }
                    }
                }
            }
         }

         if($eligible == 'no'){
             //$activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

             //return view('price-plan')->with(['activePlans'=>$activePlans]);
             return redirect('/business/price-plan');
         }

        //dd($current_toolkit->answer);
//        if(count($current_toolkit) <= 0)
        if(!$current_toolkit)
            return view('errors.404');
        elseif($current_toolkit->answer != null){
            if ($current_toolkit->answer->done == 1){
                //dd('redirection 1');
                return redirect('/business/report/'.$slug.'.html');
            }
        }

        //$toolkits = Toolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return response()->json(['toolkit' => $toolkit]);
        /*dd($current_toolkit);
        die('die');*/
        //dd('hello outter if',$toolkits);
        return view('business.toolkit')->with(['current_toolkit' => $current_toolkit]);
    }
    public function showNew($slug)
    {
        $current_toolkit = Toolkit::with('answer')->where(['slug' => $slug])->first();

        $eligible = 'yes';
        if($current_toolkit->ToolkitAssociation->count() > 0) {
            foreach ($current_toolkit->ToolkitAssociation as $association){
                if($association->keyword != 'is_signup') {
                    $asso_plan = MembershipPlans::where('id', $association->plan_id)->first();

                    //dd($asso_plan->sort_order);

                    if (($association->plan_id == Auth::User()->membership_plan_id) && ($association->enabled == 0)) {
                        $eligible = 'no';
                        break;
                    }elseif($association->enabled == 1){

                        //var_dump( Auth::User()->MembershipPlans->sort_order.'--->'.$asso_plan->sort_order);

                        if(Auth::User()->MembershipPlans->sort_order > $asso_plan->sort_order) {
                            break;
                        }
                    }
                }
            }
         }

         if($eligible == 'no'){
             //$activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

             //return view('price-plan')->with(['activePlans'=>$activePlans]);
             return redirect('/business/price-plan');
         }

        //dd($current_toolkit->answer);
//        if(count($current_toolkit) <= 0)
        if(!$current_toolkit)
            return view('errors.404');
        elseif($current_toolkit->answer != null){
            if ($current_toolkit->answer->done == 1){
                //dd('redirection 1');
                return redirect('/business/report/'.$slug.'.html');
            }
        }

        //$toolkits = Toolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return response()->json(['toolkit' => $toolkit]);
        /*dd($current_toolkit);
        die('die');*/
        //dd('hello outter if',$toolkits);
        return view('business.toolkit')->with(['current_toolkit' => $current_toolkit]);
    }

    public function showResult($slug)
    {
        $current_toolkit = Toolkit::with('answer')->where(['slug' => $slug])->first();
//        if(count($current_toolkit) <= 0)
        if(!$current_toolkit)
            return view('errors.404');
        $toolkits = Toolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //dd('redirection 2');
        return view('business.report')->with(['toolkits' => $toolkits, 'current_toolkit' => $current_toolkit]);
    }
    public function saveResult(Request $request)
    {
        $data = json_encode($request->all());
        $toolkit_id = $request->get('toolkit_id');
        $toolkit = ToolkitAnswer::where(['toolkit_id' => $request->get('toolkit_id'),'user_id' => Auth::User()->id])->first();
        //dd($data);
        //dd($toolkit->result);
        if($toolkit->result == "") {
            //dd()
            $toolkit->result = $data;
            $toolkit->save();
        }

        if ($request->get('sendEmailValue') == true){
            $toolkit_question = Toolkit::where(['id' => $request->get('toolkit_id')])->first();
            return response()->json(['url'=>route('business.readiness' ,['slug'=>$toolkit_question->slug])]);
        }

        if($request->get('retry') == true){

            $t_history = new ToolkitHistory();
            $t_history->user_id = Auth::User()->id;
            $t_history->toolkit_id = $toolkit_id;
            $t_history->answers = json_encode($request->get('answers'));
            $t_history->result = $data;
            if($request->get('compliant') != null) {
                $t_history->previous_status = $request->get('compliant');
            }
            $t_history->save();

            //dd()
            //$toolkit->delete();

            if($toolkit != null){
                ToolkitAnswer::destroy($toolkit->id);
            }


            $toolkit_question = Toolkit::where(['id' => $request->get('toolkit_id')])->first();

            return response()->json(['url'=>route('business.readiness' ,['slug'=>$toolkit_question->slug])]);
        }

        /*if ($request->get('retry') == false)
            return response()->json(['url'=>route('business.downloadResult' ,['id'=>$toolkit_id])]);
*/
        //$toolkit_question = Toolkit::where(['id' => $request->get('toolkit_id')])->first();
        //dd(route('business.report' ,['slug'=>$toolkit_question->slug]));
        //return response()->json(['url'=>route('business.report' ,['slug'=>$toolkit_question->slug])]);

        //return view('business.result')->with(['data' => $data]);
    }

    public static function emailReport($id, Request $request){

        $toolkit = ToolkitAnswer::where(['toolkit_id' => $id,'user_id' => Auth::User()->id])->first();
        //dd($toolkit->result);
        $data = json_decode($toolkit->result);
        $data->date = $toolkit->updated_at;

        $user = User::find(Auth::User()->id);


        $pdf = PDF::loadView('business.result', ['data'=>$data, 'user' => Auth::user()]);
        Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new ReportMail($data,$pdf,$user));
           //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data' => $data,'user'=>$user]
//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
    }
    public function downloadPdf($id){

        $toolkit = ToolkitAnswer::where(['toolkit_id' => $id,'user_id' => Auth::User()->id])->first();
        //dd($toolkit->updated_at);
        $data = json_decode($toolkit->result);
        $data->date = $toolkit->updated_at;
        //dd($data->date);

        $pdf = PDF::loadView('business.result', ['data'=>$data, 'user' => Auth::user()]);
        return $pdf->download('Assessment_Report.pdf');
    }
    public function showProgress()
    {
        $total_toolkits = Toolkit::where('sort_order','>','0')->count();
        $done_answers = ToolkitAnswer::where(['user_id'=>Auth::User()->id, 'done' => 1])->count();
        $progress = 0;
        $progress = ($done_answers / $total_toolkits) *100;
        $progress = (int)$progress;

        //var_dump('$progress',$progress);

        $user = User::find(Auth::User()->id);
        return response()->json(['progress' => $progress,'total_toolkits' => $total_toolkits,'done_answers' => $done_answers , 'user' => $user]);
    }
    public function saveAnswers(Request $request){
        /*$test = $request->all();
        var_dump('here');
        var_dump($test);
        die();*/
        $toolkit_answers = ToolkitAnswer::firstOrCreate(['toolkit_id' => $request->get('toolkit_id'),'user_id' => Auth::User()->id]);
        $toolkit_answers->fill($request->all());

        $toolkit_answers->save();

        $done = $request->get('done');

        if($done == true)
            return response()->json(['toolkit_answers' => $toolkit_answers]);
    }
    public function routegetAllToolkits(){
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
            'all_toolkits' => Toolkit::where('sort_order','>','0')->orderBy('sort_order', 'asc')->get(),
            'plan' => $plan
        ]);
    }
    public function routegetCurrentToolkit($id){
       return response()->json(['current_toolkit'=>Toolkit::where('id','=',$id)->first()]);
    }
    public function routegetAllAnswers(){
       return response()->json(['all_answers'=>ToolkitAnswer::where('sort_order','>','0')->orderBy('sort_order')->get(['id','name','icon','slug','icon_class','sort_order'])]);
    }
    public function routegetToolkitHistory($slug){
       return response()->json(['current_toolkit'=>Toolkit::with('answer')->where(['slug' => $slug])->first()]);
    }

}