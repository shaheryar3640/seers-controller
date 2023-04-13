<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function home()
    {
       
		
		$setid=1;
		$setts = DB::table('settings')
		->where('id', '=', $setid)
		->get();

		$data = array('setts' => $setts);
            return view('index')->with($data);
    }

    public function getCompanieslist(Request $request){

        $name = $request->get('term');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.companieshouse.gov.uk/search/companies?q=' . urlencode($name));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERPWD,"4Fu0Z8iOBX7K4GinmkwiBpccIvQeI4cS8AMx31Yh");
        $response = curl_exec($curl);

        $response_array = json_decode($response);
        //$response_array = (array)$response_array;
        $ctr = 0;
        $new_items = false;
        
        $new_items[] = [
            'title' => $name,
            'company_number'=>'',
            'address' => [
                'address_line_1'=>'',
                'locality'=>'',
                'postal_code'=>'',
                ]
        ];
        
        foreach($response_array->items as $item){
            $new_items[] = $item;
            $ctr++;
            if($ctr > 20)
                    break;
        }

        if($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            return "cURL error ({$errno}):\n {$error_message}";
        }
        curl_close($curl);

        return response()->json(['items' => $new_items]);



        //return json_encode(['success'=> true,'message'=>'helloo ' . $name]);
    }


}
