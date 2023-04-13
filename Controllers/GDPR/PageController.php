<?php

namespace App\Http\Controllers\GDPR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Models\Product;
use App\Models\StaffTraining;

class PageController extends Controller
{

    private $urls = [
        'urls' => [
            [ 'url' => 'gdpr-assessment-1', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-2', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-3', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-4', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-5', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-6', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-7', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-8', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-9', 'visited' => false ],
            [ 'url' => 'gdpr-assessment-10', 'visited' => false ],
            [ 'url' => 'final_result', 'visited' => false ],
        ]
    ];

    public function __construct()
    {

    }
    
    public function index()
    {
        
        if (auth()->check()) {
            if (!hasProduct('gdpr_training')) {
                session()->put('upgrade_plan', true);
                return redirect()->route('business.price-plan');
            }
            $employee = StaffTraining::where([
                'email' => auth()->user()->email,
                'user_id' => auth()->id(),
                'isBusiness' => 1
            ])->first();           
            if (!$employee) {
                $employee = new StaffTraining;
                $employee->email = auth()->user()->email;
                $employee->user_id = auth()->id();
                $employee->token = str_replace('/', '0hS@ls', Hash::make(auth()->user()->email));
                $employee->actual_token = str_replace('/', '0hS@ls', Hash::make(auth()->user()->email. 'npm-run-watch'));
                $employee->status = 'Reached';
                $employee->isBusiness = true;
                $employee->name = auth()->user()->name;
                $employee->questions = json_encode($this->urls);
                $employee->current_url = '/';
                $employee->save();
            } else {
                if (!($employee->current_url == "/")) {
                    return redirect()->to('gdpr-training'.$employee->current_url);
                }
            }
        }
        
        
        return view('gdpr.frontend.pages.home');
    }

    public function handlingData()
    {
        return view('gdpr.frontend.pages.handling_personal_data');
    }

    public function home($check = 0, $reset = 0)
    {
        $check1 =  $check2 = $check3 = 0;

        if($check == 1) {
            $check1 = 1;
        } elseif($check == 2) {
            $check1 = 1; $check2 = 1;
        } elseif($check == 3) {
            $check1 = 1; $check2 = 1; $check3 = 1;
        }
        
        if ($reset == 1) {
           $employee = StaffTraining::where(['email' => auth()->user()->email, 'user_id' => auth()->id(), 'isBusiness' => 1])->first();
           $employee->score = 0;
           $employee->current_url = '/';
           $employee->previous_url = NULL;
           $employee->questions = json_encode($this->urls);
           $employee->save();

            if ($employee->certificate) {
                $employee->certificate->delete();
            }
        } else if($reset == 2){
            $employee = StaffTraining::where([
                'email' => session()->get('data')['staff_email'],
                'token' => session()->get('data')['token']
                ])->first();
            $employee->score = 0;
            $employee->current_url = '/';
            $employee->previous_url = null;
            $employee->questions = json_encode($this->urls);
            $employee->save();

            if ($employee->certificate) {
                $employee->certificate->delete();
            }
        }
        return view('gdpr.frontend.pages.welcome', compact('check1','check2','check3'));
    }
    public function result()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.result_page');
    }    
    public function page2()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.page2');
    }    
    public function page20()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.page20');
    }
    public function final_assessment()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment');
    }
    public function final_assessment1()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment1');
    }
    public function final_assessment2()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment2');
    }
    public function final_assessment3()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment3');
    }
    public function final_assessment4()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment4');
    }
    public function final_assessment5()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment5');
    }
    public function final_assessment6()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment6');
    }
    public function final_assessment7()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment7');
    }
    public function final_assessment8()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment8');
    }
    public function final_assessment9()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment9');
    }
    public function final_assessment10()
    {
        // $this->setCurrentUrl();
        return view('gdpr.frontend.pages.final_assessment10');
    }

    private function setCurrentUrl()
    {
        if(!Auth::check()){
            $data = session()->get('data');
            $data['current_url'] = substr(url()->current(), strrpos(url()->current(), '/'));
            $data['previous_url'] = substr(url()->previous(), strrpos(url()->previous(), '/'));
            session()->put('data', $data);
            $emp = StaffTraining::where('email', $data['staff_email'])->first();
            $emp->current_url = $data['current_url'];
            $emp->previous_url = $data['previous_url'];
            $emp->save();
        } else {
            $emp = StaffTraining::where('email', Auth::User()->email)->first();            
            $emp->current_url = substr(url()->current(), strrpos(url()->current(), '/'));
            $emp->previous_url = substr(url()->previous(), strrpos(url()->previous(), '/'));
            $emp->save();
        }
    }
}
