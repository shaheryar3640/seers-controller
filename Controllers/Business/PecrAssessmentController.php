<?php

namespace App\Http\Controllers\Business;

use App\Mail\ReportMailPECR;
use App\Models\PECRAnswer;
use App\Models\PecrHistory;
use App\Models\PecrToolkit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use PDF;

class PecrAssessmentController extends Controller
{

    public function index() {
        if (!hasProduct('assessment', 'pecr_audit')) {
            // return redirect()->route('subscription-expired');
            return redirect()->route('price-plan');
        }
        $country = null;
        try {
            $country = $this->getCountry();
        } catch (\Exception $e) {
            $country = null;
        }

        return view('business.pecr_audit_assessment')->with([
        'country' => $country,
        ]);
    }

    public function loadAssessments() {
        if (!request()->ajax()) {
            return response(['message' => 'Baq Request'], 400);
        }

        $assessments = PecrToolkit::with('answer')
            ->where('sort_order', '>', 0)
            ->orderBy('sort_order')
            ->get();
        
        $u_plan = auth()->user()->products()->where('name', '=', 'assessment')->first()->plan;


        return response(['assessments' => $assessments,'plan_name' => $u_plan->name,'message' => 'OK'], 200);
    }

    public function saveAnswer(Request $request) {
        $assessment_answer = PECRAnswer::firstOrCreate([
            'pecr_toolkit_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ]);
        $assessment_answer->fill($request->all());
        $assessment_answer->save();
        $done = $request->get('done');
        if ($done == true) {
            $assessments = PecrToolkit::with('answer')
                ->where('sort_order', '>', 0)
                ->orderBy('sort_order')
                ->get();
            return response(['assessments' => $assessments, 'message' => 'OK'], 200);
        }
    }

    public function retryAssessment(Request $request) {

        $toolkit = PECRAnswer::where([
            'pecr_toolkit_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment_history = new PecrHistory();
        $assessment_history->user_id = auth()->id();
        $assessment_history->pecr_toolkit_id = $request->get('toolkit_id');
        $assessment_history->answers = json_encode($request->get('answers'));
        $assessment_history->result = $toolkit->result;
        if ($request->get('compliant') !== null) {
            $assessment_history->previous_status = $request->get('compliant');
        }
        $assessment_history->save();

        $assessment = PECRAnswer::where([
            'pecr_toolkit_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment->delete();

        $assessments = PecrToolkit::with('answer')
            ->where('sort_order', '>', 0)
            ->orderBy('sort_order')
            ->get();
        return response(['assessments' => $assessments, 'message' => 'OK'], 200);
    }

    public function downloadReport ($id) {
        $assessment = PECRAnswer::where([
            'pecr_toolkit_id' => $id,
            'user_id' => auth()->id()
        ])->first();
        $data = json_decode($assessment->result);
        $data->date = $assessment->updated_at;
        $pdf = PDF::loadView('business.pecr_audit_assessment_report', ['data' => $data, 'user' => auth()->user()]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->download('Assessment Report.pdf');
    }

    public function saveResults(Request $request) {
        $data = json_encode($request->all());
        $assessment = PECRAnswer::where([
            'pecr_toolkit_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment->result = $data;
        $assessment->save();

        return response(['message' => 'OK', 'data' => $data], 200);
    }

    public function sendEmail(Request $request) {
        $assessment = PECRAnswer::where([
            'pecr_toolkit_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $data = json_decode($assessment->result);
        $data->date = $assessment->updated_at;

        $user = auth()->user();

        $pdf = PDF::loadView('business.pecr_audit_assessment_report', ['data'=>$data, 'user' => $user]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
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

        // Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new ReportMailPECR($data, $pdf, $user));
    }
    private function getCountry () {
        $ip = request()->ip();
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        $country_code = \DB::connection('mysql2')->select($query);
        return $country_code && $country_code[0]->code != '-' ? $country_code : null;
    }
}
