<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Toolkit;
use App\Models\MembershipPlans;
use Auth;
use App\Models\PecrToolkit;
use App\Models\PecrHistory;
use App\Models\PECRAnswer;
use App\Models\User;
use App\Models\Product;
use PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportMailPECR;

class PECRController extends Controller
{

    public function __construct()
    {

    }

    public function index(){
        //return "You are in index";
        $toolkits = PecrToolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //$toolkits = PolicyGenerator::where('sort_order','>','0')->orderBy('sort_order')->get();
        //dd($toolkits);
        return view('pecr.assessments-toolkits')->with(['toolkits' => $toolkits]);
    }
    public function indexTest(){
        $toolkits = PecrToolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return $toolkits;
        return response()->json(['toolkits'=>$toolkits]);
    }
    public function show($slug)
    {
        if(!hasProduct('assessment', 'pecr_audit')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }
        $current_toolkit = PecrToolkit::with('answer')->where(['slug' => $slug])->first();
        return view('pecr.toolkit')->with(['current_toolkit' => $current_toolkit]);
    }
    public function showResult($slug)
    {
        $current_toolkit = PecrToolkit::with('answer')->where(['slug' => $slug])->first();
        //dd($current_toolkit);
        if($current_toolkit->count() <= 0)
            return view('errors.404');
        $toolkits = PecrToolkit::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //dd($toolkits);
        return view('pecr.report')->with(['toolkits' => $toolkits, 'current_toolkit' => $current_toolkit]);
    }

    public function saveResult(Request $request)
    {
        $data = json_encode($request->all());
        $toolkit_id = $request->get('toolkit_id');
        $toolkit = PECRAnswer::where(['pecr_toolkit_id' => $request->get('toolkit_id'),'user_id' => Auth::User()->id])->first();
        if($toolkit->result == "") {
            //dd()
            $toolkit->result = $data;
            $toolkit->save();
        }
        
        if ($request->get('sendEmailValue') == true){
            $toolkit_question = PecrToolkit::where(['id' => $request->get('toolkit_id')])->first();
            return response()->json(['url'=>route('pecr.readiness' ,['slug'=>$toolkit_question->slug])]);
        }

        if($request->get('retry') == true){

            $t_history = new PecrHistory();
            $t_history->user_id = Auth::User()->id;
            $t_history->answers = json_encode($request->get('answers'));
            $t_history->result = $data;
            $t_history->pecr_toolkit_id = $toolkit_id;
            if($request->get('compliant') != null) {
                $t_history->previous_status = $request->get('compliant');
            }
            $t_history->save();

            if($toolkit != null){
                PECRAnswer::destroy($toolkit->id);
            }

            $toolkit_question = PecrToolkit::where(['id' => $request->get('toolkit_id')])->first();

            return response()->json(['url' => route('pecr.readiness' ,['slug' => $toolkit_question->slug])]);
        }
    }

    public static function emailReport($id, Request $request){

        $toolkit = PECRAnswer::where(['pecr_toolkit_id' => $id,'user_id' => Auth::User()->id])->first();
        //dd($toolkit->result);
        $data = json_decode($toolkit->result);
        $data->date = $toolkit->updated_at;

        $user = User::find(Auth::User()->id);

        $pdf = PDF::loadView('pecr.result', ['data'=>$data, 'user' => Auth::User()]);
        
        // Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new ReportMailPECR($data, $pdf, $user));
        // PECR Audit | Sendgrid template name
        $to = ['email' => $request->get('email'), 'name' => ''];
        $template = [
            'id' => config('sendgridtemplateid.PECR-Audit'), 
            'data' => [ 
                "first_name" => $user->fname,
                'tool'=>$data->toolkit_name,
                'organisation_name'=>$user->company,
                'time'=>$data->date,
                'compl_%'=>$data->compliant,
                'non_compl_%'=>$data->inCompliant,
            ]
        ];
        $file = base64_encode($pdf->output());
        $fileDetails = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $data->toolkit_name . '.pdf' ];
        sendEmailViaSendGrid($to, $template,$fileDetails);
    }

    public function downloadPdf($id){
        // dd(Auth::user()->company);
        $toolkit = PECRAnswer::where(['pecr_toolkit_id' => $id, 'user_id' => Auth::User()->id])->first();
        //dd($toolkit);
        $data = json_decode($toolkit->result);
        $data->date = $toolkit->updated_at;
        //dd($data->date);
        // dd($data);
        $pdf = PDF::loadView('pecr.result', ['data' => $data, 'user' => Auth::user()]);
       
        return $pdf->download('Pecr_Audit_Report.pdf');
    }

    public function showProgress()
    {
        $total_toolkits = PecrToolkit::where('sort_order','>','0')->count();
        $done_answers = PECRAnswer::where(['user_id' => Auth::User()->id, 'done' => 1])->count();
        //dd($done_answers);
        $progress = 0;
        $progress = ($done_answers / $total_toolkits) * 100;
        $progress = (int) $progress;

        //var_dump('$progress',$progress);

        $user = User::find(Auth::User()->id);
        return response()->json(['progress' => $progress,'total_toolkits' => $total_toolkits,'done_answers' => $done_answers , 'user' => $user]);
    }

    public function saveAnswers(Request $request){
        $toolkit_answers = PECRAnswer::firstOrCreate(['pecr_toolkit_id' => $request->get('toolkit_id'),'user_id' => Auth::User()->id]);
        $toolkit_answers->fill($request->all());

        $toolkit_answers->save();

        $done = $request->get('done');

        if($done == true)
            return response()->json(['toolkit_answers' => $toolkit_answers]);
    }

    public function routegetAllToolkits() {
        $product = Product::where(['name' => 'assessment'])->first();
            $plan = null;
            if ($product) {
                $user = Auth::User();
                $u_product = $user->currentProduct($product->name);
                if ($u_product) {
                    $plan = $u_product->plan->name;
                }
            }
            return response()->json(
                ['all_toolkits' => PecrToolkit::where('sort_order','>','0')->orderBy('sort_order', 'asc')->get(),
                    'plan' => $plan
                    ]);
    }
    public function routegetAllAnswers() {
        return response()->json(['all_answers'=>PECRAnswer::where('sort_order','>','0')->orderBy('sort_order')->get(['id','name','icon','slug','icon_class','sort_order'])]);
    }
    public function routegetCurrentToolkit($id) {
       return response()->json(['current_toolkit'=>PecrToolkit::where('id','=',$id)->first()]);
    }
    public function routegetToolkitHistory($slug) {
        return response()->json(['current_toolkit'=>PecrToolkit::with('answer')->where(['slug' => $slug])->first()]);
    }
}