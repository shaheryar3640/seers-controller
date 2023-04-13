<?php

namespace App\Http\Controllers\Business;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Template;

class TemplateController extends Controller
{    

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($slug)
    {
        if (!hasProduct('assessment', 'privacy_template')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }

//        $first_temp = Template::where('enabled', 1)->orderBy('sort_order', 'desc')->first();
        $current_temp = Template::where('slug', $slug)->first();
        $templates = Template::where('enabled', 1)->orderBy('sort_order', 'asc')->get();

        if ($current_temp === null) {
            return view('errors.404');
        }
        
        return view('business.templates.template-pack', compact('current_temp', 'templates'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function download($slug)
    {
        $temp_doc = \App\Models\TemplateDocument::where('slug', $slug)->first();
        
        if($temp_doc != NULL){
            $file = storage_path("download").'/'. $temp_doc->file_name;
            if($file){
                return response()->download($file, $temp_doc->file_name);
            }else{
                return view('errors.404');
            }
        }

        return view('errors.404');
    }
}
