<?php

namespace App\Http\Controllers\Business;

use App\Models\CyberSecure;
use App\Models\CyberSecureAnswer;
use App\Models\CyberSecureCertificate;
use App\Models\CyberSecureHistory;
use App\Mail\CyberSecureAdvanced;
use App\Mail\CyberSecureCertificateAdvanced;
use App\Mail\CyberSecureCertificateSimple;
use App\Mail\CyberSecureReport;
use App\Mail\CyberSecureSimple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use PDF;

class CyberAssessmentController extends Controller
{
    public function index() {
        if (!hasProduct('assessment', 'cyber_secure')) {
            // return redirect()->route('subscription-expired');
            return redirect()->route('price-plan');
        }
        $country = null;
        try {
            $country = $this->getCountry();
        } catch (\Exception $e) {
            $country = null;
        }

        return view('business.cyber_secure_assessment')->with([
        'country' => $country,
        ]);
    }

    public function loadAssessments() {
        if (!request()->ajax()) {
            return response(['message' => 'Baq Request'], 400);
        }

        $u_plan = auth()->user()->products()->where('name', '=', 'assessment')->first()->plan;

        $assessments = CyberSecure::with('answer')
            ->where('sort_order', '>', 0)
            ->orderBy('sort_order')
            ->get();

        return response(['assessments' => $assessments,'plan_name' => $u_plan->name, 'message' => 'OK'], 200);
    }

    public function saveAnswer(Request $request) {
        $assessment_answer = CyberSecureAnswer::firstOrCreate([
            'cyber_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ]);
        $assessment_answer->fill($request->all());
        $assessment_answer->save();
        $done = $request->get('done');
        if ($done == true) {
            $assessments = CyberSecure::with('answer')
                ->where('sort_order', '>', 0)
                ->orderBy('sort_order')
                ->get();
            return response(['assessments' => $assessments, 'message' => 'OK'], 200);
        }
    }

    public function retryAssessment(Request $request) {

        $toolkit = CyberSecureAnswer::where([
            'cyber_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment_history = new CyberSecureHistory();
        $assessment_history->user_id = auth()->id();
        $assessment_history->cyber_id = $request->get('toolkit_id');
        $assessment_history->answers = json_encode($request->get('answers'));
        $assessment_history->result = $toolkit->result;
        if ($request->get('compliant') !== null) {
            $assessment_history->previous_status = $request->get('compliant');
        }
        $assessment_history->save();

        $assessment = CyberSecureAnswer::where([
            'cyber_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment->delete();

        $assessments = CyberSecure::with('answer')
            ->where('sort_order', '>', 0)
            ->orderBy('sort_order')
            ->get();
        return response(['assessments' => $assessments, 'message' => 'OK'], 200);
    }

    public function downloadReport ($id) {
        $assessment = CyberSecureAnswer::where([
            'cyber_id' => $id,
            'user_id' => auth()->id()
        ])->first();
        $data = json_decode($assessment->result);
        $data->date = $assessment->updated_at;
        $pdf = PDF::loadView('business.cyber_secure_assessment_report', ['data' => $data, 'user' => auth()->user()]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->download('Assessment Report.pdf');
    }

    public function saveResults(Request $request) {
        $data = json_encode($request->all());
        $assessment = CyberSecureAnswer::where([
            'cyber_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $assessment->result = $data;
        $assessment->save();

        return response(['message' => 'OK', 'data' => $data], 200);
    }

    public function sendEmail(Request $request) {
        $assessment = CyberSecureAnswer::where([
            'cyber_id' => $request->get('toolkit_id'),
            'user_id' => auth()->id()
        ])->first();
        $data = json_decode($assessment->result);
        $data->date = $assessment->updated_at;

        $user = auth()->user();

        $pdf = PDF::loadView('business.cyber_secure_assessment_report', ['data' => $data, 'user' => $user]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        $id = "";
        // if (str_slug($assessment->slug) == 'cyber-secure-premium') {
        //     $data->compliant === "100.00"
        //         ? Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureCertificateSimple($data, $pdf, $user))
        //         : Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureSimple($data, $pdf, $user));

        // } elseif ($assessment->slug == 'cyber-secure-advance') {
        //     $data->compliant === "100.00"
        //         ? Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureCertificateAdvanced($data, $pdf, $user))
        //         : Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureAdvanced($data, $pdf, $user));

        // } else {
        //     Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CyberSecureReport($data, $pdf, $user));
        // }

        if (str_slug($assessment->slug) == 'cyber-secure-premium') {
            $id = $data->compliant === "100.00" ? "" : "";
        } elseif ($assessment->slug == 'cyber-secure-advance') {
            $id = $data->compliant === "100.00" ? "" : "";
        } else {
            $id = "";
        }

        $to = [ 'email' => $request->get('email'), 'name' => '' ];
        $template = [
            'id' => $id, 
            'data' => [
                "first_name" => $user->fname,
                "tool" => "PECR Audit",
                "tool1" => $data->toolkit_name,
                "organisation_name" => $user->company ?? "Seers Group Ltd.",
                "time" => $data->date
            ]
        ];
        $file = base64_encode($pdf->output());
        $attachment = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $data->toolkit_name . '.pdf' ];
        sendEmailViaSendGrid($to, $template, $attachment);
    }

    public function getCertificate ($id) {
        $type = 'sample';
        $cyber_secure = CyberSecure::where('id', $id)->first();
        $answers = CyberSecureAnswer::where(['cyber_id' => $id,'user_id' => auth()->id()])->first();
        $data = json_decode($answers->result);
        $data->date = $answers->updated_at;
        if (str_slug($cyber_secure->slug) == 'cyber-secure-premium') {
            $type = 'premium';
        } elseif ($cyber_secure->slug == 'cyber-secure-advance') {
            $type = 'advance';
        }
        
        $certificate = CyberSecureCertificate::where(['cyber_id' => $id, 'user_id' => auth()->id(), 'type' => $type ])->first();
        if ($certificate == null) {
            $certificate = new CyberSecureCertificate();
            $certificate->serial = rand(1000000000,9999999999);
            $certificate->cyber_id = $id;
            $certificate->user_id = auth()->id();
            $certificate->type = $type;
            $certificate->org_name = auth()->user()->company;
            $certificate->org_address = auth()->user()->address;
            $certificate->issue_date = date('Y-m-d H:i:s', time());
            $certificate->expiry_date = date("Y-m-d H:i:s", strtotime("+1 year", time()));
            $certificate->save();
        }

        $pdf = PDF::loadView('business.cyber_secures.certificate', ['data' => $certificate]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->stream('Cyber Secure Certificate.pdf');
    }

    private function getCountry () {
        $ip = request()->ip();
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        $country_code = \DB::connection('mysql2')->select($query);
        return $country_code && $country_code[0]->code != '-' ? $country_code : null;
    }
}
