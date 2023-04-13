<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CookieBank;

class CookieBankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$cookie = CookieBank::find(2);
        $cookies = CookieBank::all();
        /*foreach($cookies as $cookie){
            if($cookie->id == 2){
                echo $cookie->name;
                $count = 0;
                foreach($cookie->CookieAssociation as $associate){
                    var_dump($associate->CookieDomain->name);
                    echo "<br/>";
                    if($count == 2){
                        exit();
                    }
                    $count++;
                }
                exit();
            }
        }*/
        return view('admin.cookie-bank.show', compact('cookies'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.cookie-bank.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $cookie_bank = new CookieBank();
        $cookie_bank->name = $request->get('name');
        $cookie_bank->provider = $request->get('provider');
        $cookie_bank->size = $request->get('size');
        $cookie_bank->purpose_desc = $request->get('purpose_desc');
        $cookie_bank->save();

        return redirect()->back()->with('success', 'Cookie Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDomains($id)
    {
        $cookie_bank = CookieBank::find($id);
        $cookie_domains = $cookie_bank->CookieBankDomains;
        if($cookie_domains->count() > 0){
            return view('admin.cookie-bank.showDomains', compact('cookie_domains'));
        } else {
            return dd('no domains found');
        }

        /*$cookie_domains = $cookie_bank->CookieAssociation;
        if($cookie_domains->count() > 0){
            return view('admin.cookie-bank.showDomains', compact('cookie_domains'));
        } else {
            return dd('no domains found');
        }*/
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cookie = CookieBank::find($id);
        return view('admin.cookie-bank.edit', compact('cookie'));
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
        $cookie = CookieBank::find($id);
        $cookie->name = $request->get('name');
        $cookie->purpose_desc = $request->get('purpose_desc');
        $cookie->save();
        return redirect()->back()->with('sccuess', 'Cookie Description has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
