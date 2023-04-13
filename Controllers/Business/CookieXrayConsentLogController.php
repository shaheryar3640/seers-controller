<?php

namespace App\Http\Controllers\Business;

use App\CookieXrayConsentLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use http\Client\Response;
use Illuminate\Support\Facades\Config;

class CookieXrayConsentLogController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    $this->db_name= Config::get('app.database_name');

    }

    /**
     * undocumented function
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * */
    public function getFilteredLog (Request $request) {
         
        $domain_id = $request->get('domain_id');        
        $start_date = date("Y-m-d", strtotime($request->get('start_date')));
        $end_date = date("Y-m-d",  strtotime($request->get('end_date')));
        $country = $request->get('country_name');
        $newCountries = [];

        $countries = \DB::table('cookie_xray_consent_logs')
            ->select('country')
            ->whereDomId($domain_id)
            ->groupby('country')
            ->distinct()
            ->get();

        for($i = 0; $i < count($countries); $i++) {
            $newCountry = [];
            $newCountry['name'] = $countries[$i]->country;
            $newCountry['code'] = str_slug($countries[$i]->country);
            array_push($newCountries, $newCountry);
        }
        //if country is null and date is not null or 1970
        if(($start_date != '1970-01-01' && $start_date != '') && ($end_date != '1970-01-01' && $end_date != '') && (is_null($country) || $country == null)) {
            
            $statsLogs = \DB::select("SELECT 
                COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
                COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
                COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
                COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
                COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
                COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
                COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
                COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
                FROM
                $this->db_name.cookie_xray_consent_logs
                where dom_id = $domain_id && created_at >= '$start_date' && created_at <= '$end_date'");
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('created_at', 'DESC')->paginate(10);
                
        } // if country is not null or all and date is null or 1970
        else if((!is_null($country) || $country != null) && $country !== 'all' && ($start_date == '1970-01-01' || $start_date == '') && ($end_date == '1970-01-01' || $end_date == '')) {
            
            $country = ucwords(str_replace("-", " ", $country));
            $statsLogs = \DB::select("SELECT 
                COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
                COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
                COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
                COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
                COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
                COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
                COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
                COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
                FROM
                $this->db_name.cookie_xray_consent_logs
                where dom_id = $domain_id && country = '$country'");
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->whereCountry($country)->orderBy('created_at', 'DESC')->paginate(10);
                
        } // if country is all and date is not null or 1970
        else if((!is_null($country) || $country != null) && $country == 'all' && ($start_date != '1970-01-01' && $start_date != '') && ($end_date != '1970-01-01' && $end_date != '')) {
            $statsLogs = \DB::select("SELECT 
                COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
                COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
                COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
                COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
                COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
                COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
                COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
                COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
                FROM
                $this->db_name.cookie_xray_consent_logs
                where dom_id = $domain_id && created_at >= '$start_date' && created_at <= '$end_date'");
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('created_at', 'DESC')->paginate(3000);
                
        } //if country is not null nor all and date is null or 1970
        else if((!is_null($country) || $country != null) && $country == 'all' && ($start_date == '1970-01-01' || $start_date == '') && ($end_date == '1970-01-01' || $end_date == '')) {
            $statsLogs = \DB::select("SELECT 
                COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
                COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
                COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
                COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
                COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
                COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
                COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
                COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
                FROM
                $this->db_name.cookie_xray_consent_logs
                where dom_id = $domain_id");
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->paginate(3000);
                
        } //if country is not null nor all and date is not null or 1970
        else if((!is_null($country) || $country != null) && $country != 'all' && ($start_date != '1970-01-01' || $start_date != '') && ($end_date != '1970-01-01' || $end_date != '')) {
            $country = ucwords(str_replace("-", " ", $country));
            $statsLogs = \DB::select("SELECT 
                COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
                COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
                COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
                COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
                COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
                COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
                COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
                COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
                FROM
                $this->db_name.cookie_xray_consent_logs
                where dom_id = $domain_id && created_at >= '$start_date' && created_at <= '$end_date' && country = '$country'");
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->whereCountry($country)->orderBy('created_at', 'DESC')->paginate(10);
                
        }
        else{
            $statsLogs = \DB::select("SELECT 
            COUNT(IF(necessary = 1, 1, NULL)) 'necessary',
            COUNT(IF(preferences = 1, 1, NULL)) 'preferences',
            COUNT(IF(marketing = 1, 1, NULL)) 'marketing',
            COUNT(IF(statistics = 1, 1, NULL)) 'statistics',
            COUNT(IF(unclassified = 1, 1, NULL)) 'unclassified',
            COUNT(IF(do_not_sell = 1, 1, NULL)) 'do_not_sell',
            COUNT(IF(preferences = 1 && marketing = 1 && statistics = 1, 1, NULL)) 'accepted',
            COUNT(IF(preferences = 0 || marketing = 0 || statistics = 0, 1, NULL)) 'rejected'
        FROM
        $this->db_name.cookie_xray_consent_logs
        where dom_id = $domain_id");
        $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->orderBy('created_at', 'DESC')->paginate(10);
    }
    $stats['necessary'] = $statsLogs['0']->necessary;
    $stats['preferences'] = $statsLogs['0']->preferences;
    $stats['marketing'] = $statsLogs['0']->marketing;
    $stats['statistics'] = $statsLogs['0']->statistics;
    $stats['unclassified'] = $statsLogs['0']->unclassified;
    $stats['do_not_sell'] = $statsLogs['0']->do_not_sell;
    $stats['accepted'] = $statsLogs['0']->accepted;
    $stats['rejected'] = $statsLogs['0']->rejected;

    return response()->json([
        'success' => true,
        'countries' => $newCountries,
        'log' => $logs ?? [],
        'stats' => $stats
    ], 200);       
}

    public function exportToExcel($domain_id) {

        $log = CookieXrayConsentLog::where(['dom_id' => $domain_id])->get();
        if ($log->count() > 0) {
            $file = 'Cookie Consent Log.csv';
            $filePath = storage_path('download/consent-log/'. $file);
            if (file_exists($filePath)) {
                $output = fopen($filePath, "w");
                fputcsv($output, array(
                    'Sr.#', 'IP Address', 'Country', 'Preference', 'Marketing', 'Statistics', 'Sell my Data?', 'Date And Time',
                    'User ID'
                ));
                $counter = 1;
                foreach ($log as $item) {
                    $record = [];
                    $record[] = $counter;
                    $record[] = $this->encodeIp($item->ip_address);
                    $record[] = $item->country;
                    $record[] = $item->preferences ? 'Allowed' : 'Not Allowed';
                    $record[] = $item->marketing ? 'Allowed' : 'Not Allowed';
                    $record[] = $item->statistics ? 'Allowed' : 'Not Allowed';
                    $record[] = $item->do_not_sell ? 'Allowed' : 'Not Allowed';
                    $record[] = $item->date_and_time;
                    $record[] = $item->user_id;
                    fputcsv($output, $record);
                    $counter++;
                }
                fclose($output);
                $headers = array(
                    'Content-Type'          => 'application/vnd.ms-excel; charset=utf-8',
                    'Cache-Control'         => 'must-revalidate, post-check=0, pre-check=0',
                    'Content-Disposition'   => 'attachment; filename=Consent Log.csv',
                    'Expires'               => '0',
                    'Pragma'                => 'public',
                );
                return response()->download($filePath, 'Consent Log.csv', $headers);
            }
        } else {
            return response([
                'not_found' => 'No record found'
            ], 403);
        }
    }

    public function encodeIp ($ip) {
        $new_ip = explode('.', $ip);
//        $new_ip[count($new_ip) - 2] = 'xxx';
        $new_ip[count($new_ip) - 1] = 'xxx';
        return implode('.', $new_ip);
    }
}
                