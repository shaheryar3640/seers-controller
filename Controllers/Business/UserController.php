<?php

namespace App\Http\Controllers\Business;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use phpDocumentor\Reflection\Types\Null_;

class UserController extends Controller
{
    private $user = null;
    private $features = null;
    private $product_name = 'cookie_consent';
    public function __construct()
    {
        $this->middleware('business');
        $this->user = Auth::User();
    }


    public function getAuthUserFeatures()
    {
        $this->user = Auth::User();
        $country = null;
        try {
            $country = $this->getCountry();
        } catch (\Exception $e) {
            $country = null;
        }
        return response()->json([
			'timestart' => date('i:s:su'),
            'features' => $this->getFeatures(),
            'country' => $country,
			'timeend' => date('i:s:su')
        ]);
    }

    public function getFeatures(){
        $product = $this->user->currentProduct($this->product_name);

        if($product){
            $plan = $product->plan;
            $features = $plan->features;
            if($features->count() > 0){
                foreach ($features as $feature){
                    $this->features[$feature->name] = (int)$feature->value;
                }
                $this->features['plan_name'] = $plan->name;

            }
        }
        return $this->features;
    }

    private function getCountry () {
        $ip = request()->ip();
		$country_code = \Location::get($ip); 
        return $country_code? $country_code : null;
		
        // $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        // $country_code = \DB::connection('mysql2')->select($query);
        // return $country_code && $country_code[0]->code != '-' ? $country_code : null;
    }

}
