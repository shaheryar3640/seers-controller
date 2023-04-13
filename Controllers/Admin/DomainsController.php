<?php

namespace App\Http\Controllers\Admin;

use App\Models\CbUsersDomains;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DomainsController extends Controller
{

    /**
     * responsible for displaying the view
     *
     * @params none
     *
     * @return view
     */

    public function index() {
        return view('admin.domains-list');
    }

    /**
     * Change the verified status of domain
     *
     * @params $filterId as integer
     *
     * @return response in json format
     * */

    public function show($filterId) {
        $domains = CbUsersDomains::where(['enabled' => 1, 'verified' => $filterId])->orderBy('created_at', 'DESC')->paginate(10);
        return response()->json([
            'domains' => $domains
        ], 200);
    }


    /**
     * Change the verified status of domain
     *
     * @param Request as $request
     *
     * @return response in json format
     */

    public function changeDomainStatus(Request $request) {
        $data = $request->all();
        $allSelectedDomains = CbUsersDomains::where(['name' => $data['domain_name'], 'enabled' => 1])->orderBy('created_at', 'DESC')->get();
        foreach ($allSelectedDomains as $domain) {
            if($domain->id == $data['domain_id']) {
                $domain->verified = $data['verification'];
            }
            $domain->verified = 0;
        }
        $domains = CbUsersDomains::where(['enabled' => 1, 'verified' => $data['filterId']])->orderBy('created_at', 'DESC')->paginate(10);
        return response()->json([
            'domains' => $domains
        ], 200);
    }
}

