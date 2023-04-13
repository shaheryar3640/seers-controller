<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\CbCookieRisks;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;

class CbCookieRisksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cookieRisks = CbCookieRisks::orderBy('sort_order','asc')->paginate(20);

        return view('admin.cookiebot.cookieRiskIndex', ['cookieRisks' => $cookieRisks]);
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


    public function editCookieRisk($id){
        $cookieRisk = CbCookieRisks::where('id', $id)->first();

        return view('admin.cookiebot.editRisk', ['cookieRisk' => $cookieRisk]);
    }

    public function updateCookieRisk(Request $request){

        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $cookieRisk = CbCookieRisks::find($request->get('id'));

        $cookieRisk->fill($request->all());
        $cookieRisk->save();

        return redirect(route('admin.cookieRisks'))->with('success', 'Cookie Risk updated!');

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
     * @param  \App\CbCookieRisks  $cbCookieRisks
     * @return \Illuminate\Http\Response
     */
    public function show(CbCookieRisks $cbCookieRisks)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CbCookieRisks  $cbCookieRisks
     * @return \Illuminate\Http\Response
     */
    public function edit(CbCookieRisks $cbCookieRisks)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CbCookieRisks  $cbCookieRisks
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CbCookieRisks $cbCookieRisks)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CbCookieCategories  $cbCookieRisks
     * @return \Illuminate\Http\Response
     */
    public function destroy(CbCookieRisks $cbCookieRisks)
    {
        //
    }
}
