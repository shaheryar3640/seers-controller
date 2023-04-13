<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feature;

class ProductFeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($product_id, $plan_id)
    {                
        $features = Plan::find($plan_id)->Features;
        return view('admin.products.plans.features.show', compact('features', 'product_id', 'plan_id'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($product_id, $plan_id)
    {
        return view('admin.products.plans.features.create', compact('product_id', 'plan_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $plan_id = $request->get('plan_id');
        $feature = Feature::create($request->all());
        return back()->with('success', 'Feature added successfully');
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
    public function edit($product_id, $plan_id, $f_id)
    {
        $feature = Feature::find($f_id);
        return view('admin.products.plans.features.edit', compact('product_id', 'plan_id', 'feature'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $product_id, $plan_id, $f_id)
    {
        $feature = Feature::find($f_id);
        $feature->fill($request->all());
        $feature->save();

        return back()->with('success', 'Feature updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($product_id, $plan_id, $f_id)
    {
        Feature::destroy($f_id);
        return back()->with('success', 'Feature deleted successfully');
    }
}
