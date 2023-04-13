<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\CbUsersDomains;
use App\Models\CbCookies;

use Illuminate\Http\Request;

class CbUsersDomainsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $domains = CbUsersDomains::orderBy('id','desc')->paginate(20);

        return view('admin.cookiebot.index', ['domains' => $domains]);
    }

    public function domainCookies($id){
        //var_dump('Request id', $id);
        //dd('In function fetch domain cookies');
        $domain = CbUsersDomains::where('id', $id)->first();

        $cookies = CbCookies::where('dom_id', $id)->paginate(20);
        return view('admin.cookiebot.cookieslist', ['cookies' => $cookies, 'domain' => $domain]);
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
     * @param  \App\CbUsersDomains  $cbUsersDomains
     * @return \Illuminate\Http\Response
     */
    public function show(CbUsersDomains $cbUsersDomains)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\CbUsersDomains  $cbUsersDomains
     * @return \Illuminate\Http\Response
     */
    public function edit(CbUsersDomains $cbUsersDomains)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CbUsersDomains  $cbUsersDomains
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CbUsersDomains $cbUsersDomains)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CbUsersDomains  $cbUsersDomains
     * @return \Illuminate\Http\Response
     */
    public function destroy(CbUsersDomains $cbUsersDomains)
    {
        //
    }
}
