<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;

class ProductPlansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($product_id)
    {
        $plans = Plan::where('product_id', $product_id)->orderBy('sort_order', 'desc')->get();
        // dd($plans);
        return view('admin.products.plans.show', compact('plans', 'product_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($product_id)
    {
        return view('admin.products.plans.create', compact('product_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $plan = Plan::create($request->all());
        if($plan != null){
            return back()->with('success', 'Plan created successfully');
        }
        return back()->with('error', 'Something went wrong');
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
    public function edit($product_id, $plan_id)
    {
        $plan = Plan::where(['id' => $plan_id, 'product_id' => $product_id])->first();
        return view('admin.products.plans.edit', compact('plan', 'product_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $plan_id)
    {
        $plan = Plan::where('id', $plan_id)->first();
        $plan->fill($request->all());
        $plan->save();
        return back()->with('success', 'Plan Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $plan_id)
    {
        Plan::destroy($plan_id);
        return back()->with('success', 'Plan deleted successfully');
    }
}
