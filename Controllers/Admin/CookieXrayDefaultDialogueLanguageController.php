<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CookieXrayDefaultDialogueLanguage;
use App\Imports\CookieXrayDefaultDialogueLanguageImport;
use App\Exports\CookieXrayDefaultDialogueLanguageExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;
// use Excel;


class CookieXrayDefaultDialogueLanguageController extends Controller
{
    // public function trun(){
    //     CookieXrayDefaultDialogueLanguage::truncate();
    // }
    public function index(){
        $lanData = CookieXrayDefaultDialogueLanguage::orderBy('id','desc')->get();
       return view('admin.support_languages.index',['lanData'=>$lanData]);
    }
    public function create()
    {
        return view('admin.support_languages.create');
    }
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required'
        ]);
        $d = $request->file('csv_file');
        if($request->hasFile('csv_file')){
            $file = $request->file('csv_file');
            $file_name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            if ($extension == "xlsx" || $extension == "xls" || $extension == "csv") {
                $path = $request->file('csv_file')->getRealPath();
                $data =  Excel::import(new CookieXrayDefaultDialogueLanguageImport, request()->file('csv_file'));
                return redirect('admin/support_languages')->with('success', 'CSV file Successfully Added');
            }
            else{
                return back()->with('error', 'Please upload a valid file');
            }
        }
    }
    public function export()
    {
        return Excel::download(new CookieXrayDefaultDialogueLanguageExport,
        'CookieXrayDefaultDialogueLanguage.xlsx');
        return back();
    }
    public function edit($id)
    {
        $lanData = CookieXrayDefaultDialogueLanguage::find($id);
        if(empty($lanData)){
            return redirect('admin/support_languages')->with('error','No data found');
        }
        else{
            return view('admin.support_languages.edit')->with(compact('lanData'));
        }
        
    }
    public function update(Request $request)
    {
        
    
        $request->validate([
            'name' => 'required',
            'country_code' => 'required',
            'is_active' => 'required|numeric|min:0',
            'sort_order' => 'required|numeric|min:0',
            'title' => 'required',
            'body' => 'required',
            'cookies_body' => 'required',
            'preference_title' => 'required',
            'preference_body' => 'required',
            'statistics_title' => 'required',
            'statistics_body' => 'required',
            'marketing_title' => 'required',
            'marketing_body' => 'required',
            'unclassified_title' => 'required',
            'unclassified_body' => 'required',
            'btn_agree_title' => 'required',
            'btn_disagree_title' => 'required',
            'btn_preference_title' => 'required',
            'link_more' => 'required',
            'link_less' => 'required',
            'link_view' => 'required',
            'btn_save_my_choices' => 'required',
            'btn_back' => 'required',
            'link_read_more' => 'required',
            'link_read_less' => 'required',
            'cookies_declaration' => 'required',
            'necessory_title' => 'required',
            'necessory_body' => 'required',
            'col_name' => 'required',
            'col_provider' => 'required',
            'col_purpose' => 'required',
            'col_expiry' => 'required',
            'col_type' => 'required',
            'year' => 'required',
            'years' => 'required',
            'day' => 'required',
            'days' => 'required',
            'only' => 'required',
            'http' => 'required',
            'left' => 'required',
            'expired' => 'required',
            'do_not_sell' => 'required',
            'you_can_read_our_cookie_policy_here' => 'required',
            'about_cookies' => 'required',
            'cookie_declaration_powered_by' => 'required',
            'read_cookie' => 'required',
            'policy' => 'required',
            'powered_by' => 'required'
        ]);
        $values = array_except($request->all(), ['_token']);
        $is_active = $values['is_active'];
        
        $values['is_active'] = $is_active;
        $sort_order = $values['sort_order'];
       
        $values['sort_order'] = $sort_order;
        
        $upd = CookieXrayDefaultDialogueLanguage::where('id',$request->id)->update($values);
        return redirect('admin/support_languages')->with('success', 'Language Data has been updated');
    }
}
