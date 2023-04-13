<?php

namespace App\Http\Controllers\Business;

use App\Mail\ReportMail;
use App\Mail\GDPRAuditCertificateMail;
use App\Models\Toolkit;
use App\Models\ToolkitAnswer;
use App\Models\ToolkitHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use PDF;

class GdprAssessmentController extends Controller
{

        public function index() {
           
            if (!hasProduct('assessment','gdpr_audit')) {
            // return redirect()->route('subscription-expired');
            return redirect()->route('price-plan');
        }
            $country = null;
            try {
                $country = $this->getCountry();
            } catch (\Exception $e) {
                $country = null;
            }

            return view('business.gdpr_audit_assessment')->with([
            'country' => $country,
        ]);
        }

        public function loadAssessments() {
            if (!request()->ajax()) {
                return response(['message' => 'Baq Request'], 400);
            }

            $assessments = Toolkit::with('answer')
                ->where('sort_order', '>', 0)
                ->orderBy('sort_order')
                ->get();

            $u_plan = auth()->user()->products()->where('name', '=', 'assessment')->first()->plan;

            foreach ($assessments as $assessment) {
                $plans = $assessment->assessmentPlans()->where('enabled', '=', 1)->pluck('keyword')->toArray();
                $assessment->is_available = in_array($u_plan->name, $plans) ? true : false;
            }

            return response(['assessments' => $assessments,'plan_name' => $u_plan->name,'message' => 'OK'], 200);
        }

        public function saveAnswer(Request $request) {
            $assessment_answer = ToolkitAnswer::firstOrCreate([
                'toolkit_id' => $request->get('toolkit_id'),
                'user_id' => auth()->id()
            ]);
            $assessment_answer->fill($request->all());
            $assessment_answer->save();
            $done = $request->get('done');
            if ($done == true) {
                $assessments = Toolkit::with('answer')
                    ->where('sort_order', '>', 0)
                    ->orderBy('sort_order')
                    ->get();
                
            $u_plan = auth()->user()->products()->where('name', '=', 'assessment')->first()->plan;

            foreach ($assessments as $assessment) {
                $plans = $assessment->assessmentPlans()->where('enabled','=',1)->pluck('keyword')->toArray();
//                $assessment->is_available = in_array($u_plan->name, $plans) ? true : false;
                $assessment->is_available = true;
            }
                return response(['assessments' => $assessments, 'message' => 'OK'], 200);
            }
        }

        public function retryAssessment(Request $request) {

            $toolkit = ToolkitAnswer::where([
                'toolkit_id' => $request->get('toolkit_id'),
                'user_id' => auth()->id()
            ])->first();
            $assessment_history = new ToolkitHistory();
            $assessment_history->user_id = auth()->id();
            $assessment_history->toolkit_id = $request->get('toolkit_id');
            $assessment_history->answers = json_encode($request->get('answers'));
            $assessment_history->result = $toolkit->result;
            if ($request->get('compliant') != 'NaN') {
                $assessment_history->previous_status = $request->get('compliant');
            }
        // return response(['assessments' => $request->get('compliant')]);
        
            $assessment_history->save();

            $assessment = ToolkitAnswer::where([
                'toolkit_id' => $request->get('toolkit_id'),
                'user_id' => auth()->id()
            ])->first();
            $assessment->delete();

            $assessments = Toolkit::with('answer')
                ->where('sort_order', '>', 0)
                ->orderBy('sort_order')
                ->get();
            return response(['assessments' => $assessments, 'message' => 'OK'], 200);
        }

        public function downloadReport ($id) {
            
            $assessment = ToolkitAnswer::where([
                'toolkit_id' => $id,
                'user_id' => auth()->id()
            ])->first();
            // dd($assessment);
            $data = json_decode($assessment->result);
            $data->date = $assessment->updated_at;
           
            $pdf = PDF::loadView('business.gdpr_audit_assessment_report', ['data' => $data, 'user' => auth()->user()]);
            // $pdf->SetProtection(['copy', 'print'], '', 'pass');
            return $pdf->download('Assessment Report.pdf');
        }

        public function saveResults(Request $request) {
            $data = json_encode($request->all());
            $assessment = ToolkitAnswer::where([
                'toolkit_id' => $request->get('toolkit_id'),
                'user_id' => auth()->id()
            ])->first();
            $assessment->result = $data;
            if($request->get('compliant')){
                $assessment->complaince_achieved =  floatval($request->get('compliant'));
            }            
            $assessment->save();

            return response(['message' => 'OK', 'data' => $data], 200);
        }

        public function sendEmail(Request $request) {
            $assessment = ToolkitAnswer::where([ 'toolkit_id' => $request->get('toolkit_id'), 'user_id' => auth()->id() ])->first();
            $data = json_decode($assessment->result);
            $data->date = $assessment->updated_at;

            $user = auth()->user();

            $pdf = PDF::loadView('business.gdpr_audit_assessment_report', ['data' => $data, 'user' => $user]);
            // $pdf->SetProtection(['copy', 'print'], '', 'pass');
            if($data->is_correct_answers == 1){
                $id = config('sendgridtemplateid.GDPR-Audit-Assessment-With-Compliance');
            }else{
                $id = config('sendgridtemplateid.GDPR-Audit-Assessment-Without-Compliance');
            }
            $to = [ 'email' => $request->get('email'), 'name' => '' ];
            $template = [
                'id' => $id, 
                'data' => [
                    "first_name" => $user->fname,
                    "tool" => "GDPR Audit",
                    "tool1" => $data->toolkit_name,
                    "organisation_name" => $user->company ?? "Seers Group Ltd.",
                    "time" => $data->date->toTimeString(),
                    'compl_%'=>$data->compliant,
                    'non_compl_%'=>$data->inCompliant, 
                ]
            ];
            // return $pdf->output();
            $file = base64_encode($pdf->output());
            $fileDetails = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $data->toolkit_name . '.pdf' ];
            sendEmailViaSendGrid($to, $template, $fileDetails);

            // Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new ReportMail($data, $pdf, $user));
        }
        private function getCountry () {
            $ip = request()->ip();
            $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
            $country_code = \DB::connection('mysql2')->select($query);
            return $country_code && $country_code[0]->code != '-' ? $country_code : null;
        }

        public function sendCertificate() {
            if(auth()->check()){
                $user = auth()->user();
                $data = [
                    'company' => $user->company,
                    'user_name' => ucfirst($user->fname) . ' ' . ucfirst($user->lname),
                    'issue_date' =>  date('Y-m-d H:i:s', time()),
                    'expiry_date' => date("Y-m-d H:i:s", strtotime("+1 year", time()))
                ];
    
                $pdf = PDF::loadView('gdpr.certificates.gdpr-audit-certificate', ['data'=>$data], ['format' => 'A4-L']);
                // Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new GDPRAuditCertificateMail($data, $pdf, $user));

                // GDPR Training ChildComplete Certification || sendgrid template name
                 $to = ['email' => $user->email, 'name' => $user->fname];
                $template = [
                    'id' => config('sendgridtemplateid.GDPR-Training-ChildComplete-Certification'), 
                    'data' => ['first_name'=> $user->fname]
                ];
                $file = base64_encode($pdf->output());
                $fileDetails = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $user->fname . '.pdf' ];
                sendEmailViaSendGrid($to, $template,$fileDetails);
                return response()->json(['message'=>'Email Sent Successfully'],200);
            } else {
                return response()->json(['message'=>'You are not authorized'],401);
            }


        }
        public function sendTrainingofGdpr() {
            if(!hasProduct('gdpr_training')) {
            session()->put('upgrade_plan', true);
            // return redirect()->route('subscription-expired');
            return redirect()->route('price-plan');
        }

        return view('business.training-of-gdpr');
        }



}