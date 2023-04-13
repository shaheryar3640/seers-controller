<?php

namespace App\Http\Controllers\Admin;

use App\Models\CbUsersDomains;
use App\Models\User;
use App\Models\UProduct;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\UPlan;
use Carbon\Carbon;
use Session;
use DataTables;
class DomainNameController extends Controller
{
    public function index(Request $request){
        // $spp = User::select('type')->pluck('type')->unique();
        // dd($spp);
        $plan_names = Plan::where('product_id',2)->select('name')->pluck('name')->unique();
        $domain_platforms = CbUsersDomains::where('script_platform','!=',null)->select('script_platform')->pluck('script_platform')->unique();
        // dd($domain_platforms);
        $cb = CbUsersDomains::with('user.cmp_product.plan.consent_limit_feature')
            ->has('user.cmp_product.plan')
            ->whereNotIn('name',['seersco.com','youtube.com','facebook.com'])
            ->with('dialogue.banner')
            ->whereHas('user', function ($q) {
                $q->where('email','not like', '%seersco.com%')->whereNot('email','abc@abc.com');
            })
            // ->has('user.cmp_product')
            ->has('user')
            ->select('*');
        if (isset($_GET['domain_platform']) && !empty($_GET['domain_platform'])) {
            $domain_platform = $_GET['domain_platform'];
            $cb->where('script_platform',$domain_platform);
        }
        if (isset($_GET['script']) && !empty($_GET['script'])) {
            if($_GET['script'] == 'exist'){
                $script = 1;
                $cb->where('script_exist',$script);
            }
            else if($_GET['script'] == 'nonexist'){
                $script = 0;
                $cb->where('script_exist',$script);
            }

        }
        if (isset($_GET['type']) && $_GET['type'] == 'appsumo') {
            $type = 'appsumo';
            $cb->whereHas('user', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }
        elseif(isset($_GET['type']) && $_GET['type'] == 'user'){
            $type = 'appsumo';
            $cb->whereHas('user', function ($q) use ($type) {
//                $q->where('type','!=', $type);
                $q->where('type', '=', '')
                    ->orWhereNull('type');
//                 $q->where('type', '')->orWhere('type', null);
//            $users = $users->whereNotIn('type', [$type]);
            });
        }
        if (isset($_GET['expiry_date']) && !empty($_GET['expiry_date'])) {
            if($_GET['expiry_date'] == 'expired'){
                $cb->whereHas('user.cmp_product', function ($q) {
                    $q->where('expired_on', '<', Carbon::today());
                });
            }
            else if($_GET['expiry_date'] == 'unexpired'){
                $expiry_date = $_GET['expiry_date'];
                $cb->whereHas('user.cmp_product', function ($q) {
                    $q->where('expired_on', '>=', Carbon::today());
                });
            }
        }
        if (isset($_GET['plan_name']) && !empty($_GET['plan_name'])) {
            $plan_name = $_GET['plan_name'];
            $cb->whereHas('user.cmp_product.plan', function ($q) use ($plan_name) {
                $q->where('name', '=', $plan_name);
            });
        }
        // dd($cb);
        if (isset($_GET['banner_field']) && !empty($_GET['banner_field'])) {
            if ($_GET['banner_field'] == 'expandable') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name' ,$isactive);
                });
            }
            else if ($_GET['banner_field'] == 'free') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'default') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'side_one') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'bar_with_popup') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'side_one_ccpa') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'google_banner') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'bar_with_vendor') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'iab') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'side_banner') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'popup_with_vendor') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
            else if ($_GET['banner_field'] == 'side_one_with_bar') {
                $isactive = $_GET['banner_field'];
                $cb->whereHas('dialogue.banner', function ($q) use ($isactive) {
                    $q->where('name', $isactive);
                });
            }
        }
        if ($request->ajax()) {
            return Datatables::of($cb)
                ->addColumn('email', function ($data) {
                    if (!empty($data->user->email)) {
                        $user_email = $data->user->email;
                    } else {
                        $user_email = 'No User Email';
                    }

                    return $user_email;
                })
                ->addColumn('banner_name', function ($data) {
                    if (!empty($data->dialogue->banner->name)) {
                        $banner_name = $data->dialogue->banner->name;
                    } else {
                        $banner_name = 'No banner';
                    }

                    return $banner_name;
                })
                ->addColumn('plan', function ($data) {
                    if (!empty($data->user->cmp_product->plan)) {
                        $plan_name = $data->user->cmp_product->plan->name;
                    } else {
                        $plan_name = 'No Plan';
                    }

                    return $plan_name;
                })
                ->addColumn('remaining_consent', function ($data) {
                    $remaining_consent = 0;
                    if (!empty($data->user->cmp_product->plan->consent_limit_feature)) {
                        $remaining_consent = $data->user->cmp_product->plan->consent_limit_feature->value - $data->total_consents;
                    }

                    return $remaining_consent;
                })
                ->addColumn('total_consents', function ($data) {
                    return $data->total_consents;
                })
                ->addColumn('expired_on', function ($data) {
                    $expired_on = '';
                    if (!empty($data->user->cmp_product)) {
                        $expired_on = $data->user->cmp_product->expired_on;
                    } else {
                        $expired_on = 'No Product  Expired Date';
                    }

                    return $expired_on;
                })
                ->toJson();
        }

        $total_paid_customer = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on', '>', Carbon::now())->whereHas('plan',function($q){
                $q->where('name','!=','free');
            });
        })
            ->count();
        $total_active_users = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on','>',Carbon::now());
        })
            ->count();
        $total_free_customer = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on', '>', Carbon::now())->whereHas('plan',function($q){
                $q->where('name','=','free');
            });
        })
            ->count();
        $total_standard_customer = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on', '>', Carbon::now())->whereHas('plan',function($q){
                $q->where('name','=','standard');
            });
        })
            ->count();
        $total_pro_customer = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on', '>', Carbon::now())->whereHas('plan',function($q){
                $q->where('name','pro');
            });
        })
            ->count();
        $total_premium_customer = User::whereHas('products',function($q){
            $q->where('name','cookie_consent')->where('expired_on', '>', Carbon::now())->whereHas('plan',function($q){
                $q->where('name','=','premium');
            });
        })
            ->count();
        $total_cdn_domain = CbUsersDomains::where('script_platform','=','cdn')->count();
        $total_cmp_domain = CbUsersDomains::where('script_platform','=','cmp')->count();
        $total_consents = CbUsersDomains::sum('total_consents');
        return view('admin.domain_name.index',compact('plan_names','domain_platforms','total_consents','total_paid_customer','total_active_users','total_free_customer','total_standard_customer','total_pro_customer','total_premium_customer','total_cdn_domain','total_cmp_domain'));
    }


    //============

}

