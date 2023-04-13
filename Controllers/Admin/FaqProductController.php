<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FaqProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $productFaqs = \App\Models\FaqProduct::orderBy('created_at', 'desc')->get();        
        return view('admin.product-faq.show', compact('productFaqs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.product-faq.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = \App\Models\FaqProduct::create($request->all()); 
        if($product){
            return redirect()->back()->with('success', 'Faq Product has been added');
        }
        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
        $faq_product = \App\Models\FaqProduct::find($id);
        return view('admin.product-faq.edit', compact('faq_product'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function open($id)
    {
        
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $faq_product = \App\Models\FaqProduct::find($id);
        $faq_product = $faq_product->fill($request->all());
        $faq_product->save();
        return back()->with('success', 'Faq Product has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $faq_product = \App\Models\FaqProduct::find($id);
        // dd($faq_product);
        if($faq_product){
            \App\Models\FaqProduct::destroy($faq_product->id);
        }
        return back();
    }
}
