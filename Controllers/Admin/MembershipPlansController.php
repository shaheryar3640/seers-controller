<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\MembershipPlans;
use App\Models\MembershipPlanSettings;
use App\Models\PlanToolkitAssociations;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;

class MembershipPlansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $membershipPlans = MembershipPlans::orderBy('sort_order','asc')->paginate(20);

        return view('admin.membershipPlans.index', ['membershipPlans' => $membershipPlans]);
    }


    public function rules(){
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|regex:/^\d*(\.\d{1,2})?$/',
        ];
    }

    public function messages(){
        return [
            'name.required' => 'Name is required',
            'description.required' => 'Description is required',
            'price.required' => 'Price is required',
        ];
    }

    public function validator(array $data)
    {
        return Validator::make($data, $this->rules(), $this->messages());
    }


    public function editMembershipPlan($id){
        $membershipPlan = MembershipPlans::where('id', $id)->first();

        return view('admin.membershipPlans.edit', ['membershipPlan' => $membershipPlan]);
    }

    public function updateMembershipPlan(Request $request){

        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $membershipPlan = MembershipPlans::find($request->get('id'));


        $membershipPlan->fill($request->all());

        if($request->get('enabled') == null){
            $membershipPlan->enabled = 0;
        }else{
            $membershipPlan->enabled = 1;
        }

        $membershipPlan->save();

        return redirect(route('admin.membershipPlans'))->with('success', 'Membership Plan updated!');

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.membershipPlans.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }
        //dd($request->all());
        $membershipPlan = MembershipPlans::create($request->all());

        $membershipPlan->slug = str_replace(' ', '-', strtolower($request->get('name')));

        if($request->get('enabled') == null){
            $membershipPlan->enabled = 0;
        }else{
            $membershipPlan->enabled = 1;
        }

        $membershipPlan->save();

        return redirect(route('admin.membershipPlans'))->with('success', 'Membership Plan Created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\MembershipPlans  $membershipPlans
     * @return \Illuminate\Http\Response
     */
    public function show(MembershipPlans $membershipPlans)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\MembershipPlans  $membershipPlans
     * @return \Illuminate\Http\Response
     */
    public function edit(MembershipPlans $membershipPlans)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\MembershipPlans  $membershipPlans
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MembershipPlans $membershipPlans)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\MembershipPlans  $membershipPlans
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //dd('Delete', $id);
        $membershipPlan = MembershipPlans::find($id);
        $planToolkitAssociations = PlanToolkitAssociations::where('plan_id', $id)->get();
        //$receivers = CbReportsReceivers::where('dom_id', $id)->get();
        if($planToolkitAssociations->count() > 0) {
            foreach ($planToolkitAssociations as $planToolkitAssociation){
                //$planToolkitAssociation->delete();
                PlanToolkitAssociations::destroy($planToolkitAssociation->id);
            }
        }

        $membershipPlanSettings = MembershipPlanSettings::where('plan_id', $id)->get();
        if($membershipPlanSettings->count() > 0) {
            foreach ($membershipPlanSettings as $membershipPlanSetting){
                //$membershipPlanSetting->delete();
                MembershipPlanSettings::destroy($membershipPlanSetting->id);
            }
        }
        //$membershipPlan->delete();
        MembershipPlans::destroy($membershipPlan->id);
        //return response()->json(['message' => 'Your Membership Plans has been deleted']);
        return back();
    }
}
