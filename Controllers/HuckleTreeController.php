<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HuckleTreeController extends Controller
{
    //
    // public function huckletree(){
    //     return view('partner.huckletree');
    // }
    
    // public function aaAccountantUK(){
    //     return view('partner.aaaccountantuk');
    // }
    
    // public function fintechcircle(){
    //     return view('partner.fintechcircle');
    // }
    
    //  public function bpf(){
    //     return view('partner.bpf');
    // }

    // public function scottServices(){
    //     return view('partner.scott-services');
    // }

    // public function agnon(){
    //     return view('partner.agnon');
    // }

    // public function gordonmcquater(){
    //     return view('partner.gordonmcquater');
    // }

    // public function cyberwebpage(){
    //     return view('partner.cyberwebpage');
    // }

    // public function spoondo(){
    //     return view('partner.spoondo');
    // }

    // public function bmBusinessManagement(){
    //     return view('partner.bm-business-management');
    // }

    // public function rsAccountants(){
    //     return view('partner.rs-accountants');
    // }
    // public function eq4(){
    //     return view('partner.eq4');
    // }
    // public function hotCreative(){
    //     return view('partner.hot-creative');
    // }

    public function showPage($page = null) {
        
        if(!is_null($page) && $page != null && $page != '') {

            $pages = [

                'huckletree',
                'aaaccountantuk',
                'fintechcircle',
                'bpf',
                'scott-services',
                'agnon',
                'gordonmcquater',
                'cyberwebpage',
                'spoondo',
                'bm-business-management',
                'rs-accountants',
                'hot-creative',
                'maplewharf-accountancy',
                'national-information-technology-development-agency',
                'kean-tyson-technology',
                'business-first-network',
                'r3-mortgages-ltd',
                'bccs',
                'devon-webs-design-hosting',
                'redtech-digital',
                'castleview-accountancy',
                'robert-thorne-fcca',
                'aurora-it-solutions',
                'dlm-group',
                'dps',
                'beacon-consultant-services',
                'consent',
                'gdpr-one-legal',
                'dpo-for-education',
                'in-the-know',
                'privacy-matters',
                'privacy-team',
                'kazient',
                'my-data-protection',
                'damm-solutions',
                'compliance-clarity',
                'leanne-georgiades',
            ];

            if(in_array($page, $pages)) {
                return view('partner.'. $page);
            } else {
                abort(404);
            }

        } else {
            abort(404);
        }
    }
}
