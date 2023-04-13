<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\CbCookieCategories;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;

class CbCookieCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cookieCategories = CbCookieCategories::orderBy('sort_order','asc')->paginate(20);

        return view('admin.cookiebot.cookieCategoryIndex', ['cookieCategories' => $cookieCategories]);
    }


    public function rules(){
        return [
            'name' => 'required|string|max:255'
        ];
    }

    public function messages(){
        return [];
    }

    public function validator(array $data)
    {
        return Validator::make($data, $this->rules(), $this->messages());
    }


    public function editCookieCategory($id){
        $cookieCategory = CbCookieCategories::where('id', $id)->first();

        return view('admin.cookiebot.editCategory', ['cookieCategory' => $cookieCategory]);
    }

    public function updateCookieCategory(Request $request){

        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $cookieCategory = CbCookieCategories::find($request->get('id'));

        $cookieCategory->fill($request->all());
        $cookieCategory->save();

        return redirect(route('admin.cookieCategories'))->with('success', 'Cookie Category updated!');

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CbCookieCategories  $cbCookieCategories
     * @return \Illuminate\Http\Response
     */
    public function show(CbCookieCategories $cbCookieCategories)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CbCookieCategories  $cbCookieCategories
     * @return \Illuminate\Http\Response
     */
    public function edit(CbCookieCategories $cbCookieCategories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CbCookieCategories  $cbCookieCategories
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CbCookieCategories $cbCookieCategories)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CbCookieCategories  $cbCookieCategories
     * @return \Illuminate\Http\Response
     */
    public function destroy(CbCookieCategories $cbCookieCategories)
    {
        //
    }
}
