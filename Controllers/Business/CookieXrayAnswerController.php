<?php

namespace App\Http\Controllers\Business;

use App\Models\CookieXray;
use App\Models\CookieXrayAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MembershipPlans;
use Auth;

class CookieXrayAnswerController extends Controller
{
    public function __construct()
    {
        $this->middleware('business');
    }

    public function show($slug)
    {
        $current_cyber_secure = CookieXray::with('answer')->where(['slug' => $slug])->first();
        //dd($current_cyber_secure);
        $eligible = 'yes';
        if($current_cyber_secure->CyberSecureAssociation->count() > 0) {
            foreach ($current_cyber_secure->CyberSecureAssociation as $association){
                if($association->keyword != 'is_signup') {
                    $asso_plan = MembershipPlans::where('id', $association->plan_id)->first();
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
        // dd($current_cyber_secure);
        if($current_cyber_secure->count() <= 0)
            return view('errors.404');
        elseif($current_cyber_secure->answer != null){
            if ($current_cyber_secure->answer->done == 1){
                return redirect('/business/cyberSecure/report/'.$slug.'.html');
            }
        }
        //$cyber_secures = CyberSecure::with('answer')->where('sort_order','>','0')->orderBy('sort_order')->get();
        //return response()->json(['cyber_secures' => $cyber_secures]);
        /*dd($current_cyber_secure);
        die('die');*/
        //dd('hello outter if',$cyber_secures);
        return view('business.cyber_secures.index')->with(['current_cyber_secure' => $current_cyber_secure]);
    }

    public function saveAnswers(Request $request){
        /* $test = $request->all();
         var_dump('here');
         var_dump($test);
         die();*/
        $cookie_xray_answers = CookieXrayAnswer::firstOrCreate(
            [
                'cookie_xray_id'    => $request->get('cookieXray_id'),
                'domain_id'         => $request->get('domain_id'),
                'user_id'           => Auth::User()->id
            ]);
        $cookie_xray_answers->fill($request->all());

        $cookie_xray_answers->save();

        $done = $request->get('done');

        if($done == true)
            return response()->json(['cookie_xray_answers' => $cookie_xray_answers]);
    }

    public function showProgress()
    {
//        $total_cookie_xray = CookieXray::where('sort_order','>','0')->count();
        $total_cookie_xray = 1;
        $done_answers = CookieXrayAnswer::where(['user_id'=>Auth::User()->id, 'done' => 1])->count();
        $progress =0;
        $progress =($done_answers / $total_cookie_xray) *100;
        $progress =(int)$progress;

        //var_dump('$progress',$progress);

        $user = User::find(Auth::User()->id);
        return response()->json([
            'progress' => $progress,
            'total__cookie_xray' => $total_cookie_xray,
            'done_answers' => $done_answers , 'user' => $user
        ]);
    }
    public function routegetCurrentCookieXray($id)
    {
        return response()->json(['current_cookieXray'=>\App\Models\CookieXray::where('id','=',$id)->first()]);
    }
    public function routeTest()
    {
         return view('business.cookiebot.test');
    }
}