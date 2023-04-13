<?php

namespace App\Http\Controllers\Business;
use App\CbCookies;
use App\CookieXrayAnswer;
use App\CookieXrayDialogue;
use App\CookieXrayPolicy;
use App\CookieXrayScript;
use App\Events\DomainHasDeletedEvent;
use App\Http\Controllers\Controller;
use App\Mail\AddDomain;
use App\Mail\DomainDestroy;
use App\UFeature;
use App\UPlan;
use App\UProduct;
use App\User;
use Auth;
use App\CbUsersDomains;
use App\CbReportsReceivers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CbUsersDomainsController extends Controller
{
    private $user = null;
    private $features = null;
    private $product_name = 'cookie_consent';
    private $view = 'error_message_page';
    private $message_code = '500';
    private $message = 'You are not eligible for this product!';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('business');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if(!hasProduct('cookie_consent')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }
        if($this->getProduct()){
            $this->view = 'business.cookiebot.index';
        }

        $data = [
            'message_code' => $this->message_code,
            'message' => $this->message
        ];

        return view($this->view)->with($data);

    }

    public function getAllDomains(){
        $domains = CbUsersDomains::where('user_id', Auth::User()->id)->with(/*'CbReportsReceivers', 'DomainsReports', */'dialogue')->get();
        return response()->json(['domains' => $domains], 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeDomain(Request $request)
    {
        $this->user = Auth::User(); // get current authenticated user.
        if(!$this->getFeatures()){ //check if user is eligible
            return response([
                'message' => '<h3><strong>Sorry! You are not eligible for this product.</strong></h3>
                              <h5>Your subscription to Cookie Consent may expired or domain limit has been exceeded.</h5 > '
            ]);
        }

        $validator = Validator::make($request->only('scan_email', 'domain_name', 'scan_frequency'), CbUsersDomains::getDomainRules(), CbUsersDomains::getDomainMessages());

        if ($validator->fails()) {
            return response([ 'errors' => $validator->errors() ], 400);
        }

        $domains = CbUsersDomains::where(['user_id'=>$this->user->id])->get();

        // if domain limit exceeded, return.
        if ($domains && $domains->count() > $this->features['domain_limit']) {
            return response(['url' => route('business.upgrade-more-domains')], 400);
        }

        $dom_name = $this->removeProtocol($request->get('domain_name'));
        $domainFinal = explode("/", $dom_name);
        if ( checkdnsrr($domainFinal[0], 'ANY') === false) {
            return response(['message' => 'You have enter an invalid domain. Please enter a valid domain address'], 400);
        }

        $domain = CbUsersDomains::where(['name' => $dom_name, 'user_id' => $this->user->id])->first();
        if($domain != null){
            return response(['message'=>'You already added same domain before.'], 400);
        }

        $domain = new CbUsersDomains();
        $domain->user_id = $this->user->id;
        $domain->name = $dom_name;
        $domain->slug = $dom_name. '-' . rand(99, 9999) . '-' . Auth::User()->id;
        $domain->scan_frequency = $request->get('scan_frequency'); // need to check in controller as well.
        $domain->platform = 'custom';
        $domain->save();

        $reportTo = new CbReportsReceivers();
        $reportTo->user_id = Auth::User()->id;
        $reportTo->dom_id = $domain->id;
        $reportTo->receiver_email = $request->get('scan_email');
        $reportTo->save();

        Mail::to($reportTo->receiver_email)->bcc(config('app.hubspot_bcc'))->send(new AddDomain($domain));

        return response(['message' => '<h3><strong>Well done! Your scan is under way</strong></h3>']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editDomain(Request $request)
    {
        $request->validate(CbUsersDomains::getReceiverRules());

        $dom_name = str_replace(array('www.', 'http://', 'https://'), array('', '', '', ''), strtolower($request->get('domain_name')));

        $domain = CbUsersDomains::where(['name' => $dom_name, 'user_id' => Auth::User()->id])->first();

        if($domain != null){
            return response()->json(['message'=>'Same domain is present in your list.'],400);
        }

        $domain = CbUsersDomains::find($request->get('domain_id'));
        //$domain->name = $dom_name;
        $domain->scan_frequency = $request->get('scan_frequency');
        $domain->save();

        $receiver = CbReportsReceivers::find($request->get('receiver_id'));
        if($receiver){
            $receiver->receiver_email = $request->get('scan_email');
            $receiver->save();
        }else{
            $reportTo = new CbReportsReceivers();
            $reportTo->user_id = Auth::User()->id;
            $reportTo->dom_id = $domain->id;
            $reportTo->receiver_email = $request->get('scan_email')?$request->get('scan_email'):auth()->user()->email;
            $reportTo->save();
        }


        return response()->json(['message' => 'Domain information is updated.']);
        //return response()->json(['reportTo' => $receiver,'domain' => $domain]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $domain = CbUsersDomains::find($id);
        event(new DomainHasDeletedEvent($domain, auth()->id()));

        return response(['message' => 'Your domain has been deleted'], 200);
    }

    public function sendVerificationReq(Request $request){
        $verified = false;
        $domain = CbUsersDomains::whereIdAndUserId($request->get('id'), Auth::user()->id)->first();
        if($domain) {
            $domain->verification_req = true;
            $domain->verification_req_on = Carbon::now();
            $domain->save();
            $verified = $domain->verified;
        }

        return response()->json([
            'message' => 'Verification Request Sent Successfully',
            'verified' => $verified,
            'domain' => $domain
        ], 200);
    }

   public function getAllUfeatures($name)
   {
//       return response([
//                'request'=>$name,
//        ],200);

        $features = UProduct::where(['name'=>$name,'user_id' => Auth::User()->id], UPlan::where(['u_product_id'=>UProduct.id]),UFeature::where(['u_plan_id'=>UPlan.id]))->first();
        return response()->json(['features' => $features]);
   }

    public function removeProtocol ($url) {
        $remove = array("http://","https://", "www.", "WWW.");
        $final_url =  str_replace($remove,"",$url);
        if(strpos($final_url, '/') !== false){
            $host_name = explode('/', $final_url);
            return $host_name[0];
        }else{
            return $final_url;
        }
    }

    public function getProduct(){
        $this->user = Auth::User();
        return $this->user->getActiveProduct($this->product_name);
    }

    public function getAuthUserFeatures()
    {
        $this->user = Auth::User();
        return response()->json([
            'features'=>$this->getFeatures()
        ]);
    }

    public function getFeatures(){
        $product = $this->user->getActiveProduct($this->product_name);
        if($product){
            $features = $product->plan->features;
            if($features->count() > 0){
                foreach ($features as $feature){
                    $this->features[$feature->name] = (int)$feature->value;
                }
            }
        }
        return $this->features;
    }
}
