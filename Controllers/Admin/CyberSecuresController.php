<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\CyberSecure;
use App\Models\MembershipPlans;
use App\Models\PlanCyberSecureAssociations;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class CyberSecuresController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('admin');
    }
    public function index()
    {
        $cyber_secures = CyberSecure::orderBy('sort_order','desc')
					->get();

        return view('admin.cyberSecures.show', ['cyber_secures' => $cyber_secures]);
    }

    /* Create */
    public function create()
    {
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

        return view('admin.cyberSecures.create', ['activePlans' => $activePlans]);
    }

    /* Design */
    public function design($id)
    {
        $cyber_secure = CyberSecure::find($id);
        //dd($cyber_secure);
        $cyber_secure_data = ($cyber_secure->cyber_secure_data);
        //dd($cyber_secure_data);
        return view('admin.cyberSecures.toolkitEditor',['cyber_secure' => $cyber_secure, 'id' => $id, 'cyber_secure_name'=>$cyber_secure->cyber_secure_name,'cyber_secure_data'=>$cyber_secure_data]);
    }

    /* Update Design */
    public function updateDesign($id, Request $request)
    {

        //$toolkitData= $request->get('toolkit_data');
        //$toolkit_data = json_decode($toolkitData);
        //$toolkit_data = serialize($request->get('questions'));
        $cyber_secure = CyberSecure::find($id);
        $cyber_secure->questions = $request->get('questions');
        $cyber_secure->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /* Add */
    public function store(Request $request)
    {

        $data = $request->all();

        $cyber_secure_name = $data['name'];
        $cyber_secure = CyberSecure::create($request->all());

        if($request->get("is_signup") == null){
            $toolkitAssociation = new PlanCyberSecureAssociations();
            $toolkitAssociation->cyber_id = $cyber_secure->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 0;
            $toolkitAssociation->save();
        }else{
            $toolkitAssociation = new PlanCyberSecureAssociations();
            $toolkitAssociation->cyber_id = $cyber_secure->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 1;
            $toolkitAssociation->save();
        }


        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $toolkitAssociation = new PlanCyberSecureAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->cyber_id = $cyber_secure->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 0;
                $toolkitAssociation->save();
            }else{
                $toolkitAssociation = new PlanCyberSecureAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->cyber_id = $cyber_secure->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 1;
                $toolkitAssociation->save();
            }
        }

        //$cyber_secure = DB::insertGetId('insert into toolkits (toolkit_name) values (?)', [$toolkit_name]);
        //$toolkit_id = DB::table('toolkits')->insertGetId(['toolkit_name' => $toolkit_name]);

        return redirect(route('admin.cyber_secures.design',['id'=>$cyber_secure->id]));
    }


    /* Edit */
    public function info($id)
    {
//        dd($id);
        $cyber_secure = CyberSecure::find($id);
        //dd($toolkit);
        return response()
            ->json($cyber_secure);

    }

    public function update($id, Request $request){
        //dd($request->all());
        $cyber_secure = CyberSecure::find($id);
        $cyber_secure->fill($request->all());
        $cyber_secure->save();

        $oldPlanCyberSecureAssociations = PlanCyberSecureAssociations::where('cyber_id', $cyber_secure->id)->get();

        if($oldPlanCyberSecureAssociations->count() > 0) {
            foreach ($oldPlanCyberSecureAssociations as $oldPlanCyberSecureAssociation){
                //$oldPlanToolkitAssociation->delete();
                PlanCyberSecureAssociations::destroy($oldPlanCyberSecureAssociation->id);
            }
        }

        if($request->get("is_signup") == null){
            $toolkitAssociation = new PlanCyberSecureAssociations();
            $toolkitAssociation->cyber_id = $cyber_secure->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 0;
            $toolkitAssociation->save();
        }else{
            $toolkitAssociation = new PlanCyberSecureAssociations();
            $toolkitAssociation->cyber_id = $cyber_secure->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 1;
            $toolkitAssociation->save();
        }


        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $toolkitAssociation = new PlanCyberSecureAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->cyber_id = $cyber_secure->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 0;
                $toolkitAssociation->save();
            }else{
                $toolkitAssociation = new PlanCyberSecureAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->cyber_id = $cyber_secure->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 1;
                $toolkitAssociation->save();
            }
        }


        return back()->with('success', 'Cyber secures has been updated');

    }


    /* Delete */
	
	public function destroy($id) {
        //DB::delete('delete from cyber_secures where id = ?',[$id]);

        $cyber_secure = CyberSecure::find($id);
        //$cyber_secure->delete();
        if($cyber_secure != null){
            CyberSecure::destroy($id);
        }
	   
        return back();
      
   }
	
}