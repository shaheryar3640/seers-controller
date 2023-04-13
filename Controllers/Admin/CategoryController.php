<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorCategory;
use App\Models\MembershipPlans;
use App\Models\PlanCategoryAssociation;
use Illuminate\Support\Facades\Redirect;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {        
        
        $categories = PolicyGeneratorCategory::orderBy('sort_order', 'desc')->get();  
              
        return view('admin.policygenerator.category.show')->with('categories', $categories);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $activePlans = MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        // dd('In create');
        return view('admin.policygenerator.category.create')->with('activePlans', $activePlans);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        // dd($data);
        $category_name = $data['name'];
        $category = PolicyGeneratorCategory::create($request->all());
        //dd($toolkit);
        
        if($request->get("is_signup") == null){
            $categoryAssociation = new PlanCategoryAssociation();
            $categoryAssociation->policy_generator_category_id = $category->id;
            $categoryAssociation->keyword = "is_signup";
            $categoryAssociation->enabled = 0;
            $categoryAssociation->save();
        }else{
            $categoryAssociation = new PlanCategoryAssociation();
            $categoryAssociation->policy_generator_category_id = $category->id;
            $categoryAssociation->keyword = "is_signup";
            $categoryAssociation->enabled = 1;
            $categoryAssociation->save();
        }

        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $categoryAssociation = new PlanCategoryAssociation();
                $categoryAssociation->plan_id = $activePlan->id;
                $categoryAssociation->policy_generator_category_id = $category->id;
                $categoryAssociation->keyword = $activePlan->slug;
                $categoryAssociation->enabled = 0;
                $categoryAssociation->save();
            }else{
                $categoryAssociation = new PlanCategoryAssociation();
                $categoryAssociation->plan_id = $activePlan->id;
                $categoryAssociation->policy_generator_category_id = $category->id;
                $categoryAssociation->keyword = $activePlan->slug;
                $categoryAssociation->enabled = 1;
                $categoryAssociation->save();
            }
        }

        return back()->with('success', 'Category added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    // Showing all policies related to given category ID,
    public function open($catId)
    {        
        return redirect(route('admin.policies.show', $catId));//->to('//category/'.$id.'/policy');//view(route('admin.policies.show', $id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($catId)
    {
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('admin.policygenerator.category.edit',['category'=>\App\Models\PolicyGeneratorCategory::find($catId), 'activePlans' => $activePlans]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $catId)
    {
        $category = PolicyGeneratorCategory::find($catId);
        $category->fill($request->all());        
        $category->save();
                        
        $oldPlanCategoryAssociations = PlanCategoryAssociation::where('policy_generator_category_id', $category->id)->get();

        if($oldPlanCategoryAssociations->count() > 0) {
           foreach ($oldPlanCategoryAssociations as $oldPlanCategoryAssociation){
               //$oldPlanToolkitAssociation->delete();
               PlanCategoryAssociation::destroy($oldPlanCategoryAssociation->id);
           }
        }

        if($request->get("is_signup") == null){
            $categoryAssociation = new PlanCategoryAssociation();
            $categoryAssociation->policy_generator_category_id = $category->id;
            $categoryAssociation->keyword = "is_signup";
            $categoryAssociation->enabled = 0;
            $categoryAssociation->save();
        }else{
            $categoryAssociation = new PlanCategoryAssociation();
            $categoryAssociation->policy_generator_category_id = $category->id;
            $categoryAssociation->keyword = "is_signup";
            $categoryAssociation->enabled = 1;
            $categoryAssociation->save();
        }


        $activePlans = MembershipPlans::orderBy('sort_order', 'asc')->get();
        //dd($activePlans);

        foreach($activePlans as $activePlan){
            if($request->get($activePlan->slug) == null){
                $categoryAssociation = new PlanCategoryAssociation();
                $categoryAssociation->plan_id = $activePlan->id;
                $categoryAssociation->policy_generator_category_id = $category->id;
                $categoryAssociation->keyword = $activePlan->slug;
                $categoryAssociation->enabled = 0;
                $categoryAssociation->save();
            }else{
                $categoryAssociation = new PlanCategoryAssociation();
                $categoryAssociation->plan_id = $activePlan->id;
                $categoryAssociation->policy_generator_category_id = $category->id;
                $categoryAssociation->keyword = $activePlan->slug;
                $categoryAssociation->enabled = 1;
                $categoryAssociation->save();
            }
        }

        return back()->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($catId)
    {
        $category = PolicyGeneratorCategory::find($catId);
        //$policy_generator->delete();
        if($category != null){
            PolicyGeneratorCategory::destroy($category->id);
        }
        return back();
    }
}
