<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Toolkit;
use App\Models\MembershipPlans;
use App\Models\PlanToolkitAssociations;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class ToolkitsController extends Controller
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
        $toolkits = Toolkit::orderBy('sort_order','desc')
					->get();

        return view('admin.toolkits.show', ['toolkits' => $toolkits]);
    }

    /* Create */
    public function create()
    {
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

        return view('admin.toolkits.create', ['activePlans' => $activePlans]);
    }

    /* Design */
    public function design($id)
    {
        $toolkit = Toolkit::find($id);
        //dd($toolkit);
        $toolkit_data = ($toolkit->toolkit_data);
        return view('admin.toolkits.toolkitEditor',['toolkit' => $toolkit, 'id' => $id, 'toolkit_name'=>$toolkit->toolkit_name,'toolkit_data'=>$toolkit_data]);
    }

    /* Update Design */
    public function updateDesign($id, Request $request)
    {

        //$toolkitData= $request->get('toolkit_data');
        //$toolkit_data = json_decode($toolkitData);
        //$toolkit_data = serialize($request->get('questions'));
        $toolkit = Toolkit::find($id);
        $toolkit->questions = $request->get('questions');
        $toolkit->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /* Add */
    public function store(Request $request)
    {

        $data = $request->all();

        $toolkit_name=$data['name'];
        $toolkit = Toolkit::create($request->all());

        if($request->get("is_signup") == null){
            $toolkitAssociation = new PlanToolkitAssociations();
            $toolkitAssociation->toolkit_id = $toolkit->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 0;
            $toolkitAssociation->save();
        }else{
            $toolkitAssociation = new PlanToolkitAssociations();
            $toolkitAssociation->toolkit_id = $toolkit->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 1;
            $toolkitAssociation->save();
        }


        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $toolkitAssociation = new PlanToolkitAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->toolkit_id = $toolkit->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 0;
                $toolkitAssociation->save();
            }else{
                $toolkitAssociation = new PlanToolkitAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->toolkit_id = $toolkit->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 1;
                $toolkitAssociation->save();
            }
        }

        //$toolkit = DB::insertGetId('insert into toolkits (toolkit_name) values (?)', [$toolkit_name]);
        //$toolkit_id = DB::table('toolkits')->insertGetId(['toolkit_name' => $toolkit_name]);

        return redirect(route('admin.toolkits.design',['id'=>$toolkit->id]));
    }

    public function edit($id)
    {
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $toolkit = Toolkit::with('ToolkitAssociation')->where('id', '=', $id)->first();
        return view('admin.toolkits.edit',['toolkit' => $toolkit, 'activePlans' => $activePlans]);
    }


    /* Edit */
    public function info($id)
    {
//        dd($id);
        $toolkit = Toolkit::find($id);
        //dd($toolkit);
        return response()
            ->json($toolkit);

    }

    public function update($id, Request $request){
        //dd($request->all());
        $toolkit = Toolkit::find($id);
        $toolkit->fill($request->all());
        $toolkit->save();

        $oldPlanToolkitAssociations = PlanToolkitAssociations::where('toolkit_id', $toolkit->id)->get();

        if($oldPlanToolkitAssociations->count() > 0) {
           foreach ($oldPlanToolkitAssociations as $oldPlanToolkitAssociation){
               //$oldPlanToolkitAssociation->delete();
               PlanToolkitAssociations::destroy($oldPlanToolkitAssociation->id);
           }
        }

        if($request->get("is_signup") == null){
            $toolkitAssociation = new PlanToolkitAssociations();
            $toolkitAssociation->toolkit_id = $toolkit->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 0;
            $toolkitAssociation->save();
        }else{
            $toolkitAssociation = new PlanToolkitAssociations();
            $toolkitAssociation->toolkit_id = $toolkit->id;
            $toolkitAssociation->keyword = "is_signup";
            $toolkitAssociation->enabled = 1;
            $toolkitAssociation->save();
        }


        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();
        //dd($activePlans);

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $toolkitAssociation = new PlanToolkitAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->toolkit_id = $toolkit->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 0;
                $toolkitAssociation->save();
            }else{
                $toolkitAssociation = new PlanToolkitAssociations();
                $toolkitAssociation->plan_id = $activePlan->id;
                $toolkitAssociation->toolkit_id = $toolkit->id;
                $toolkitAssociation->keyword = $activePlan->slug;
                $toolkitAssociation->enabled = 1;
                $toolkitAssociation->save();
            }
        }
        return back()->with('success', 'Toolkit has been updated');

    }


    /* Delete */
	
	public function destroy($id) {

      //DB::delete('delete from toolkits where id = ?',[$id]);
        $toolkit = Toolkit::find($id);
        //$toolkit->delete();
        if($toolkit != null){
            Toolkit::destroy($toolkit->id);
        }
	   
        return back();
      
   }
	
}