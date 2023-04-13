<?php

namespace App\Http\Controllers\Admin;


use App\Models\CookieXrayPlanAssociation;
use App\Http\Controllers\Controller;
use App\Models\CookieXray;
use App\Models\MembershipPlans;
use App\Models\PlanToolkitAssociations;
use Illuminate\Http\Request;

class CookieXrayController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');


    }

    public function index()
    {
        $cookie_xrays = CookieXray::orderBy('sort_order', 'desc')->get();

        return view('admin.cookie_xray.show', ['cookie_xrays' => $cookie_xrays]);
    }

    /* Create */
    public function create()
    {
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

        return view('admin.cookie_xray.create', ['activePlans' => $activePlans]);
    }

    /* Add */
    public function store(Request $request)
    {

        $data = $request->all();
        //dd($data);
        $toolkit_name=$data['name'];
        $toolkit = CookieXray::create($request->all());
//        dd($toolkit);

        $toolkitAssociation = new PlanToolkitAssociations();
        $toolkitAssociation->toolkit_id = $toolkit->id;
        $toolkitAssociation->keyword = "is_signup";
        if($request->get("is_signup") == null){
            $toolkitAssociation->enabled = 0;
        }else{
            $toolkitAssociation->enabled = 1;
        }
        $toolkitAssociation->save();

        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();

        foreach($activePlans as $activePlan){
            $toolkitAssociation = new CookieXrayPlanAssociation();
            $toolkitAssociation->plan_id = $activePlan->id;
            $toolkitAssociation->cookie_xray_id = $toolkit->id;
            $toolkitAssociation->keyword = $activePlan->slug;
            if($request->get($activePlan->slug) == null){
                $toolkitAssociation->enabled = 0;
            }else{
                $toolkitAssociation->enabled = 1;
            }
            $toolkitAssociation->save();
        }

        return redirect(route('admin.cookie_xray.design',['id'=>$toolkit->id]));
    }

    /* Design */
    public function design($id)
    {
        $toolkit = CookieXray::find($id);
        // dd($toolkit);
        $toolkit_data = ($toolkit->toolkit_data);
        //dd($toolkit_data, "This is usman");
        return view('admin.cookie_xray.toolkitEditor',['toolkit' => $toolkit, 'id' => $id, 'toolkit_name'=>$toolkit->name,'toolkit_data'=>$toolkit_data]);
    }
    /* Update Design */
    public function updateDesign($id, Request $request)
    {

        //$toolkitData= $request->get('toolkit_data');
        //$toolkit_data = json_decode($toolkitData);
        //$toolkit_data = serialize($request->get('questions'));
        $toolkit = CookieXray::find($id);
        $toolkit->questions = $request->get('questions');
        $toolkit->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }
    public function routeCookieXrayEdit($id){
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('admin.toolkits.edit',['toolkit'=>CookieXray::find($id), 'activePlans' => $activePlans]);
    }
}