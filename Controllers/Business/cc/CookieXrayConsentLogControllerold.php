<?php

namespace App\Http\Controllers\Business\cc;

use App\CookieXrayConsentLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use http\Client\Response;

class CookieXrayConsentLogController extends Controller
{

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
        $cookieTypes = $request->get('cookie_type');
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

        $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->orderBy('created_at', 'DESC');
        $statsLogs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->orderBy('created_at', 'DESC')->get();

        if ($start_date != '1970-01-01' && $end_date != '1970-01-01') {
            dd(date("Y-m-d h:i:s", strtotime($start_date)));
            $logs = $logs->where("created_at", ">=", date("Y-m-d h:i:s", strtotime($start_date)))
                ->where("created_at", "<=", date("Y-m-d h:i:s", strtotime($end_date)));

            $statsLogs = $statsLogs->where("created_at", ">=", date("Y-m-d h:i:s", strtotime($start_date)))
                ->where("created_at", "<=", date("Y-m-d h:i:s", strtotime($end_date)));
        }

        if ((!is_null($country) || $country != null) && $country !== 'all') {
            $country = ucwords(str_replace("-", " ", $country));
            $logs = $logs->where('country', '=' ,$country);

            $country = ucwords(str_replace("-", " ", $country));
            $statsLogs = $statsLogs->where('country', '=' ,$country);
        }

        $stats['preferences'] = $statsLogs->filter(function ($obj) {
            return $obj->preferences == 1;
        })->count();

        $stats['marketing'] = $statsLogs->filter(function ($obj) {
            return $obj->marketing == 1;
        })->count();

        $stats['statistics'] = $statsLogs->filter(function ($obj) {
            return $obj->statistics == 1;
        })->count();

        $stats['necessary'] = $statsLogs->filter(function ($obj) {
            return $obj->necessary == 1;
        })->count();

        $stats['unclassified'] = $statsLogs->filter(function ($obj) {
            return $obj->unclassified == 1;
        })->count();

        $stats['do_not_sell'] = $statsLogs->filter(function ($obj) {
            return $obj->do_not_sell == 1;
        })->count();

        $stats['accepted'] = $statsLogs->filter(function ($obj) {
            return $obj->preferences == 1 AND $obj->marketing == 1 AND $obj->statistics == 1;
        })->count();

        $stats['rejected'] = $statsLogs->filter(function ($obj) {
            return $obj->preferences == 0 OR $obj->marketing == 0 OR $obj->statistics == 0;
        })->count();

//        $log = new CookieXrayConsentLog();
//
//        $log = $log->select('*');
//
//        if ($domain_id != 0) {
//            $log = $log->where('dom_id', '=', $domain_id);
//        }

//        if ($start_date != '1970-01-01' && $end_date != '1970-01-01') {
//            $log = $log->where("created_at", ">=", date("Y-m-d h:i:s", strtotime($start_date)))
//                    ->where("created_at", "<=", date("Y-m-d h:i:s", strtotime($end_date)));
//        }
//
//        if ((!is_null($country) || $country != null) && $country !== 'all') {
//            $country = ucwords(str_replace("-", " ", $country));
//            $log = $log->where('country', '=' ,$country);
//        }

//        if (is_array($cookieTypes) && count($cookieTypes) > 0) {
//            foreach ($cookieTypes as $cookie) {
//                $log = $log->where($cookie['code'], '=', true);
//            }
//        }
//
//        $log = $log->orderBy('created_at', 'DESC');
        if ($country === 'all') {
            $logs = $logs->paginate(3000);
        } else {
            $logs = $logs->paginate(10);
        }
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
                