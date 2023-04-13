<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\PromoCodes;
use App\Models\Product;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;

class PromoCodesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getplandata(Request $request){
        $plans =Plan::where('product_id',$request->search)->get();
        return response()->json(['status'=>200, "data" => $plans]);

        }

        public function searchuser (Request $request){
    	$data = [];
        if($request->has('term')){
            $search = $request->term;
            $data =User::select("id","email")
            		->where('email','LIKE',"%$search%")
            		->get();
        }
        return response()->json($data);
    }


    public function index()
    {
        $promoCodes = PromoCodes::orderBy('sort_order','asc')->with(['product','user','plan'])->paginate(5);
        return view('admin.promoCodes.index', ['promoCodes' => $promoCodes]);
    }


    public function rules(){
        return [
            'promo_code' => 'required|string|max:255'
        ];
    }

    public function messages(){
        return [];
    }

    public function validator(array $data)
    {
        return Validator::make($data, $this->rules(), $this->messages());
    }


    public function editPromoCodes($id){
        $promoCode = PromoCodes::with('product','plan')->where('id', $id)->first();
        // dd($promoCode);
        $products=Product::all();
        $plans=Plan::all();

        return view('admin.promoCodes.edit', ['promoCode' => $promoCode,'products'=>$products,'plans'=>$plans]);
    }

    public function updatePromoCodes(Request $request){

        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $promoCode = PromoCodes::find($request->get('id'));

        $promoCode->fill($request->all());

        if($request->get('enabled') == null){
            $promoCode->enabled = 0;
        }else{
            $promoCode->enabled = 1;
        }

        if($request->get('is_recursive') == null){
            $promoCode->is_recursive = 0;
        }else{
            $promoCode->is_recursive = 1;
        }

        $promoCode->save();

        return redirect(route('admin.promoCodes'))->with('success', 'Promo Code updated!');

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products=Product::all();
        return view('admin.promoCodes.create',compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->except('_token'));

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }
       //        dd($request->except('_token'));
        $promoCode = PromoCodes::create($request->except('_token'));

        $promoCode->slug = str_replace(' ', '-', strtolower($request->get('promo_code')));

        $promoCode->expire_at = $request->get('expire_at').' '."23:59:59";
        if($request->get('enabled') == null){
            $promoCode->enabled = 0;
        }else{
            $promoCode->enabled = 1;
        }

        if($request->get('is_recursive') == null){
            $promoCode->is_recursive = 0;
        }else{
            $promoCode->is_recursive = 1;
        }

        $promoCode->save();

        return redirect(route('admin.promoCodes'))->with('success', 'Promo Code Created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PromoCodes  $promoCodes
     * @return \Illuminate\Http\Response
     */
    public function show(PromoCodes $promoCodes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PromoCodes  $promoCodes
     * @return \Illuminate\Http\Response
     */
    public function edit(PromoCodes $promoCodes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PromoCodes  $promoCodes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PromoCodes $promoCodes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PromoCodes  $promoCodes
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $promoCode = PromoCodes::find($id);
        //$promoCode->delete();
        if($promoCode != null) {
            PromoCodes::destroy($promoCode->id);
        }

        return back();
    }
}
