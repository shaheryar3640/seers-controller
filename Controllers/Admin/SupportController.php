<?php

namespace App\Http\Controllers\Admin;

use App\Models\CbUsersDomains;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invoice;
use App\Models\UProduct;
use App\Models\UFeature;
use App\Models\Product;
use App\Models\Plan;
use Carbon\Carbon;
use Exception;
class SupportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data['user']=User::leftJoin('u_products', 'users.id', '=', 'u_products.user_id')
        ->leftJoin('invoices', 'users.id', '=', 'invoices.user_id')
        ->leftJoin('u_plans', 'u_products.id', '=', 'u_plans.u_product_id')
        ->leftJoin('u_features', 'u_plans.id', '=', 'u_features.u_plan_id')
        ->orderby('invoices.id','desc')
        ->where('users.email',$request->email)
        ->where('u_features.name','consent_log_limit')
        ->select('invoices.id as invoice_id','users.id as user_id','users.email','users.type as type', 'u_products.id as u_product_id','u_products.expired_on as pro_expired','u_products.upgraded_on as plan_start_date','u_products.recursive_status as plan_type','u_products.purchased_on as purchase_date','u_plans.id','u_plans.display_name as feat_name','u_features.value')
        ->first();


        $data['user_from_domain_limit'] = User::with('cmp_product.plan.domain_limit')
        ->where('email',$request->email)->first();

        if($data['user'] && $request->email){
        $postData = json_encode([
            "userId" => $data['user']->user_id,
            "domain" => $request->domain,
            "key" => $request->key
        ]);
    }
       else if(!$data['user'] && $request->email){
           $postData = json_encode([
            "userId" =>0.1,
            "domain" => $request->domain,
            "key" => $request->key
        ]);
        }
        else{
            $postData = json_encode([
                "userId" =>'',
                "domain" => $request->domain,
                "key" => $request->key
            ]);
       }
        $domain = curl_request('POST', config('app.cmp_url')."/api/auth/getDomainsKey", $postData);
        $data['domains'] = json_decode($domain);
        // dd($data['domains']);

        if(!$data['user'] && $data['domains']){
            $data['users']=[''];
            $data['domain_from_domain_limit'] = [''];
            foreach($data['domains'] as $key=>$row){
            $userinfo=User::leftJoin('u_products', 'users.id', '=', 'u_products.user_id')
            ->leftJoin('invoices', 'users.id', '=', 'invoices.user_id')
            ->leftJoin('u_plans', 'u_products.id', '=', 'u_plans.u_product_id')
            ->leftJoin('u_features', 'u_plans.id', '=', 'u_features.u_plan_id')
            ->orderby('invoices.id','desc')
            ->where('users.id',$row->user_id)
            ->select('invoices.id as invoice_id','users.id as user_id','users.email', 'u_products.id as u_product_id','u_products.expired_on as pro_expired','u_products.upgraded_on as plan_start_date','u_products.recursive_status as plan_type','u_products.purchased_on as purchase_date','u_plans.id','u_plans.display_name as feat_name','u_features.value')
            ->first();
            $data['users'][$key]=$userinfo;
            }
            foreach($data['domains'] as $key=> $row){
                $domain_limit_info = User::with('cmp_product.plan.domain_limit')
                ->where('id',$row->user_id)->first();
                $data['domain_from_domain_limit'][$key]=$domain_limit_info;
            }
    }
        $data['email']=$request->email;
        $data['domain']=$request->domain;
        $data['key']=$request->key;




        return view('admin.support.index',$data);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pro = User::with('products.plan.consent_limit_feature')->has('products')->whereHas('products',function($q){
            $q->where('name','cookie_consent');
        })
        ->where('id',$id)->get();

        if(empty($pro[0]->id)){
            return redirect('admin/support')->with('error','No data found');
        }
        else{
            return view('admin.support.edit')->with(compact('pro'));
        }


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'value' => 'required|numeric'
        ]);
        $updateprice = UProduct::where('user_id',$request->id)->where('name','cookie_consent')->first();
        $updateprice->price = $request->price;
        $updateprice->save();
        $updateprice->price = $request->price;
        $updateprice =$updateprice->plan->consent_limit_feature;
        $updateprice->value = $request->value;
        $updateprice->save();
        return back()->with('success','Updates Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function plans($id){
        $userId = $id;
        $data = Product::with('plans')->where('is_active',1)->where('name','cookie_consent')->first();
        $selectplan = UProduct::with('plan')->where('user_id',$id)->where('name','cookie_consent')->first();

        $fordate = ['month','year'];
        if(empty($selectplan->user_id)){
            return redirect('admin/support')->with('error','No data found');
        }
        else{
            return view('admin.support.plan')->with(compact('data','selectplan','userId','fordate'));
        }

    }
    public function updateplan(Request $request){
        if($request->date == 'month'){
            $date = Carbon::now()->addMonth();
        }
        else{
            $date = Carbon::now()->addYear();
        }
         $selectplan = Plan::with('product')->where('id',$request->planname)->first();
         $getUplan = UProduct::with('plan')->where('user_id',$request->id)->where('name','cookie_consent')->first();

        $deleteAllUfeatures = UFeature::where('u_plan_id',$getUplan->plan->id)->delete();
         foreach($selectplan->features as $f){
             $ufeature = new UFeature;
             $ufeature->u_plan_id = $getUplan->plan->id;
             $ufeature->name = $f->name;
             $ufeature->display_name = $f->display_name;
             $ufeature->value = $f->value;
             $ufeature->price = $f->price;
             $ufeature->description = $f->description;
             $ufeature->is_visible = $f->is_visible;
             $ufeature->is_active = $f->is_active;
             $ufeature->sort_order = $f->sort_order;
             $ufeature->save();

         }
         $getUplan->price = $selectplan->product->price;
         $getUplan->description = $getUplan->description . ' updated by support panel';
         $getUplan->expired_on = $date;
         $getUplan->upgraded_on = Carbon::now();
         $getUplan->purchased_on = Carbon::now();
         $getUplan->save();

         $getUplan->plan->name = $selectplan->name;
         $getUplan->plan->display_name = $selectplan->display_name;
         $getUplan->plan->slug = $selectplan->slug;
         $getUplan->plan->description = $selectplan->description . ' updated by support panel';
         $getUplan->plan->price = $selectplan->price;
         $getUplan->plan->sort_order = $selectplan->sort_order;
         $getUplan->plan->is_active = $selectplan->is_active;
         $getUplan->plan->purchased_on = Carbon::now();
         $getUplan->plan->upgraded_on = Carbon::now();
         $getUplan->plan->expired_on = $date;
         $getUplan->plan->save();
         return back()->with('success','Plan Updated Successfully');
    }
    public function invoices($id){
        $invoices = Invoice::where('user_id',$id)->get();
        if(count($invoices)>0){
            return view('admin.support.invoices')->with(compact('invoices'));
        }
        else{
            return redirect('admin/support')->with('error','No Invoices Found');
        }
    }


    public function scan_now($id){
        //for scan now

            $postData = json_encode([
                "id" => $id
            ]);

        $domainId = curl_request('get', config('app.cmp_url')."/api/auth/domain_scan_now/".$id,'');
        $domainscan = json_decode($domainId);

        if($domainscan->type == 'success'){
            return redirect('admin/support')->with('success', 'Well done! Your scan is under way');
        }
        else{
            return redirect('admin/support')->with('error', 'already scaning');
        }

    }
    public function check_script($id){
        $script = CbUsersDomains::find($id);
        $sitename = $script->name;
        // dd($domain);
        // $arrsites = ["Www.Google.com"];
        // $sitename = $domain;
        $error=[];
        //    $arrsites=  \App\CbUsersDomains:: select('name','id','script_exist')->where('script_exist',0)->cursor();
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        // foreach ($arrsites as $sitename) {

            $valid = filter_var($sitename, FILTER_VALIDATE_IP);

            // echo "Sitename = " . $sitename . "\n";
            try {

                $htmlText = file_get_contents('https://' . $sitename, false, stream_context_create($arrContextOptions));
                // dd($htmlText);
            } catch (Exception $e) {
                //            $htmlText = file_get_contents('http://' . $sitename->name, false, stream_context_create($arrContextOptions));
                $error[]=$sitename;
                // continue;
            }


            if(!empty($htmlText)){
                    $cbscriptfound = ((strpos($htmlText, 'data-name="CookieXray"') !== false) ? true : false );
                if (!$cbscriptfound)
                {
                    $previousUrl = app('url')->previous();
                    return redirect()->to($previousUrl)->with('error', 'Script Does Not Exist');
                    // continue;
                }
                else
                {
                    \App\CbUsersDomains::find($script->id)->update([
                        'script_exist'=>1
                    ]);
                    $previousUrl = app('url')->previous();
                    return redirect()->to($previousUrl)->with('success', 'Script Exist');

                    //    echo "Sitename = " . $sitename . "\n";
                }
            }
            else{
                $previousUrl = app('url')->previous();
                return redirect()->to($previousUrl)->with('error', 'Script Does Not Exist');
            }
        // }

        // $previousUrl = app('url')->previous();
        // if($script->script_exist == 1){
        //     return redirect()->to($previousUrl)->with('success', 'Script exist');
        // }
        // else{
        //     return redirect('admin/support')->to($previousUrl)->with('success', 'Script does not exist');
        // }

    }
}
