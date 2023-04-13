<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\GDPRTrainingCertificateMail;
use Auth;
use PDF;
use App\Models\StaffCertificate;
use App\Models\StaffTraining;

class StaffAuthorizationController extends Controller
{
    public function updateValues(Request $request)
    {
        if($request->has('authUser'))
        {
            $employee = StaffTraining::where([
                'email' => auth()->user()->email,
                'user_id' => auth()->id(),
                'isBusiness' => 1
            ])->first();

            $_link = json_decode($employee->questions);
            foreach ($_link->urls as $key => $link)
            {
                if ('/'.$link->url == $request->get('current_url') && $link->visited != true) {
                    if($request->get('value') == 1) {
                        $employee->score += intval($request->get('score'));
                    }
                    $employee->previous_url = $request->get('current_url');
                    $employee->current_url = '/'.$_link->urls[$key + 1]->url;
                    $link->visited = true;
                    $employee->questions = json_encode($_link);
                }
            }
        } else {

            $employee = StaffTraining::where([
                'email' => session()->get('data')['staff_email'],
                'token' => session()->get('data')['token']
            ])->first();
            $_link = json_decode($employee->questions);
            foreach ($_link->urls as $key => $link) {
                if ('/'.$link->url == $request->get('current_url') && $link->visited != true) {
                    if ($request->get('value') == 1) {
                        $employee->score += intval($request->get('score'));
                    }
                    $employee->previous_url = $request->get('current_url');
                    $employee->current_url = '/'.$_link->urls[$key + 1]->url;
                    $link->visited = true;
                    $employee->questions = json_encode($_link);
                }
            }
        }
        $employee->save();
        return response()->json(['data' => $employee]);
    }

    public function getResult($email)
    {
        if (auth()->check()) {
            $employee = StaffTraining::where(['email' => $email, 'user_id' => auth()->id(), 'isBusiness' => 1])->first();
        } else {
            $employee = StaffTraining::where(['email' => session()->get('data')['staff_email'], 'token' => session()->get('data')['token']])->first();
        }
        return response()->json(['result' => $employee->score]);
    }

    public function generateCertificate($email)
    {
        if (auth()->check()) {
            $employee = StaffTraining::where(['email' => $email, 'user_id' => auth()->id(), 'isBusiness' => 1])->first();
            $company = $employee->company;
            $address = $employee->address;
        } else {
            $employee = StaffTraining::where(['email' => $email, 'token' => session()->get('data')['token']])->first();
            $company = $employee->owner->company;
            $address = $employee->owner->address;
        }
        if ($employee->certificate) {
            $certificate = $employee->certificate;
        } else {
            $certificate = StaffCertificate::create([
                'score' => $employee->score,
                'emp_id' => $employee->id,
                'name' => $employee->name,
                'company' => $company,
                'address' => $address,
                'serial' => rand(1000000000,9999999999),
                'issue_date' => date('Y-m-d H:i:s', time()),
                'expiry_date' => date("Y-m-d H:i:s", strtotime("+2 year", time()))
            ]);
        }
        $pdf = PDF::loadView('gdpr.certificates.certificate', ['certificate' => $certificate]);
       
        return $pdf->download('GDPR Training Certificate.pdf');
    }

    public function sendTrainingEmail(Request $request)
    {
        if (auth()->check()) {
            $employee = StaffTraining::where(['email' => auth()->user()->email, 'user_id' => auth()->id(), 'isBusiness' => 1])->first();
            $company = $employee->company;
            $address = $employee->address;
        } else {
            $employee = StaffTraining::where(['email' => session()->get('data')['staff_email'], 'token' => session()->get('data')['token']])->first();
            $company = $employee->owner->company;
            $address = $employee->owner->address;
        }
        if($employee->certificate) {
            $certificate = $employee->certificate;
        } else {
            $certificate = StaffCertificate::create([
                'score' => $employee->score,
                'emp_id' => $employee->id,
                'name' => $employee->name,
                'company' => $company,
                'address' => $address,
                'serial' => rand(1000000000,9999999999),
                'issue_date' => date('Y-m-d H:i:s', time()),
                'expiry_date' => date("Y-m-d H:i:s", strtotime("+2 year", time()))
            ]);
        }
        $pdf = PDF::loadView('gdpr.certificates.certificate', ['certificate' => $certificate]);
      
        // Mail::to($request->get('email_address'))->bcc(config('app.hubspot_bcc'))->send(new GDPRTrainingCertificateMail($pdf, $employee));
        // GDPR Training ChildComplete Certification | sendgrid template name
        $to = ['email' => $request->get('email_address'), 'name' => $employee->name];
        $template = [
            'id' => config('sendgridtemplateid.GDPR-Training-ChildComplete-Certification'), 
            'data' => [
                'first_name'=> $employee->name
            ]
        ];
        $file = base64_encode($pdf->output());
        $fileDetails = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $employee->name . '.pdf' ];
        sendEmailViaSendGrid($to, $template,$fileDetails);
        
    }

    public function getScore() {
        $score = 0;
        if (auth()->check()) {
            $employee = StaffTraining::where(['email' => auth()->user()->email, 'isBusiness' => 1])->first();
        } else {
            $employee = StaffTraining::where(['email' => session()->get('data')['staff_email'], 'token' => session()->get('data')['token']])->first();
        }
        if ($employee !== null) {
            $score = $employee->score;
        }
        return response()->json(['result' => $score]);
    }
}
