<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\MembershipPlanSettings;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;

class MembershipPlansSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $membershipPlansSettings = MembershipPlanSettings::orderBy('sort_order','asc')->paginate(20);

        return view('admin.membershipPlans.settingsIndex', ['membershipPlansSettings' => $membershipPlansSettings]);
    }


    public function rules(){
        return [
            'scan_pages_limit' => 'required|integer|min:0'
        ];
    }

    public function messages(){
        return [];
    }

    public function validator(array $data)
    {
        return Validator::make($data, $this->rules(), $this->messages());
    }


    public function editMembershipPlanSettings($id){
        //dd('hit');
        $membershipPlanSettings = MembershipPlanSettings::where('id', $id)->first();

        return view('admin.membershipPlans.editSettings', ['membershipPlanSettings' => $membershipPlanSettings]);
    }

    public function updateMembershipPlanSettings(Request $request){

        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $membershipPlanSettings = MembershipPlanSettings::find($request->get('id'));

        $membershipPlanSettings->fill($request->all());

        if($request->get('enabled') == null){
            $membershipPlanSettings->enabled = 0;
        }else{
            $membershipPlanSettings->enabled = 1;
        }

        $membershipPlanSettings->save();

        return redirect(route('admin.membershipPlans'))->with('success', 'Membership Plan Settings updated!');

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($plan_id)
    {
        return view('admin.membershipPlans.createSettings', ['plan_id' => $plan_id]);
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
        $membershipPlanSettings = MembershipPlanSettings::create($request->all());

        if($request->get('enabled') == null){
            $membershipPlanSettings->enabled = 0;
        }else{
            $membershipPlanSettings->enabled = 1;
        }

        $membershipPlanSettings->save();

        return redirect(route('admin.membershipPlans'))->with('success', 'Membership Plan Settings Created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\MembershipPlanSettings  $membershipPlanSettings
     * @return \Illuminate\Http\Response
     */
    public function show(MembershipPlanSettings $membershipPlanSettings)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\MembershipPlanSettings  $membershipPlanSettings
     * @return \Illuminate\Http\Response
     */
    public function edit(MembershipPlanSettings $membershipPlanSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\MembershipPlanSettings  $membershipPlanSettings
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MembershipPlanSettings $membershipPlanSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\MembershipPlanSettings  $membershipPlanSettings
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $membershipPlanSetting = MembershipPlanSettings::find($id);
        //$membershipPlanSetting->delete();
        if($membershipPlanSetting != null){
            MembershipPlanSettings::destroy($membershipPlanSetting->id);
        }

        return back();
    }
}
