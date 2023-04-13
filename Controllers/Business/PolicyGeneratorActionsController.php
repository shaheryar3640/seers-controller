<?php

namespace App\Http\Controllers\Business;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PolicyGeneratorActionsController extends Controller
{
    public function deleteDocument($id)
    {
        return response()->json(['res' => \App\Models\Document::where(['policy_generator_policy_id' => $id, 'user_id' => Auth::user()->id])->delete()]);
    }

    public function getPolicyHistory($id)
    {
        return response()->json(['current_policy'=> \App\Models\PolicyGeneratorSection::with('answer')->where(['policy_generator_policy_id' => $id])->get()]);
    }

    public function getComplitionDetail($id)
    {
        return response()->json(['policy_detail' => \App\Models\PolicyGeneratorAnswer::where(['policy_generator_policy_id' => $id, 'user_id' => Auth::User()->id])->get()]);
    }

    public function getAllCategories()
    {
        $product = \App\Models\Product::where(['name' => 'assessment'])->first();
        $plan = null;
        if ($product) {
            $user = Auth::User();
            $u_product = $user->currentProduct($product->name);
            if ($u_product) {
                $plan = $u_product->plan->name;
            }
        }
        return response()->json(
            ['all_categories'=> \App\Models\PolicyGeneratorCategory::where('sort_order','>','0')->where('enabled', 1)->orderBy('sort_order', 'desc')->get(),
                'plan' => $plan
            ]);
    }

    public function getCurrentCategoryPolicies($id){
        $policies = \App\Models\PolicyGeneratorPolicy::where(['policy_generator_category_id' => $id, 'enabled' => 1])->orderBy('sort_order', 'desc')->get();
        return response()->json(['category_policies' => $policies]);
    }

    public function getCurrentPolicy($id){
        return response()->json(['current_policy' => \App\Models\PolicyGeneratorPolicy::where(['id' => $id])->orderBy('sort_order', 'asc')->get()]);
    }

    public function getCurrentPolicySections($id){
        return response()->json(['sections' => \App\Models\PolicyGeneratorSection::where(['policy_generator_policy_id' => $id, 'enabled' => 1])->orderBy('sort_order', 'asc')->get()]);
    }

    public function getCurrentSection($id){
        return response()->json(['current_section' => \App\Models\PolicyGeneratorSection::where(['id' => $id, 'enabled' => 1])->get()]);
    }

    public function allPolicies(){
        return response()->json(['all_Policies_with_document' => \App\Models\PolicyGeneratorAnswer::groupBy('policy_generator_policy_id')->having('user_id', Auth::user()->id)->get()]);
    }
}
