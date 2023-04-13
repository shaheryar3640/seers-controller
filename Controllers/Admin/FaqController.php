<?php

namespace App\Http\Controllers\Admin;



use App\Models\Faq;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index($id)
    {


        $faqs = \App\Models\FaqProduct::find($id)->faqs;

        return view('admin.product-faq.faq.show', compact('faqs', 'id'));
    }

    public function create($id) {
        return view('admin.product-faq.faq.create', compact('id'));
    }

    protected function store($id, Request $request)
    {
        Faq::create($request->all());
        return back()->with('success', 'Faq has been created successfully!');
    }

	public function edit($pid, $fid) {

        $faq = Faq::where(['faq_product_id' => $pid, 'id' => $fid])->first();
        // dd($faq);
        return view('admin.product-faq.faq.edit',['faq' => $faq, 'id' => $pid]);
   }


    protected function update($pid , $fid, Request $request)
    {
        // dd($request->all());
        $data = $request->all();
        $faq = Faq::find($fid);
        $faq->fill($data);
        $faq->save();
        return back()->with('success', 'Faq has been updated successfully!');
    }

	public function destroy($pid, $fid) {

        Faq::destroy($fid);
        return back()->with('success', 'Faq has been delete successfully!');
   }

}
