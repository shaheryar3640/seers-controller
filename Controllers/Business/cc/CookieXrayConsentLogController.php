<?php

namespace App\Http\Controllers\Business\cc;

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
    $countries_data = $this->getCountryCode();
    $countriesStat = \DB::table('cookie_xray_consent_logs')
        ->select(['country',\DB::raw("(COUNT(*)) 'value'")])
        ->whereDomId($domain_id)
        ->groupby('country')
        ->distinct()
        ->get();
    if(count($countriesStat) > 0){
        foreach($countriesStat as $countryStat){
            foreach($countries_data as $country_name => $country_code ){
                if($country_name == $countryStat->country){
                    $countryStat->id = $country_code;
                }
            }
        }
    }
    return response()->json([
        'success' => true,
        'countries' => $newCountries,
        'log' => $logs ?? [],
        'stats' => $stats,
        'countryStats'=>$countriesStat,
    ], 200);       
}

    public function exportToExcel(request $request) {
        //$log = CookieXrayConsentLog::where(['dom_id' => $domain_id])->get();
        $domain_id = $request->get('domain_id');
        if($request->get('start_date')!="undefined"){
            $start = \DateTime::createFromFormat('D M d Y H:i:s e+', $request->get('start_date'));
            $start_date = $start->format('Y-m-d');
        }else{
            $start_date = date("Y-m-d", strtotime($request->get('start_date')));
        }
         if($request->get('end_date')!="undefined"){
            $end = \DateTime::createFromFormat('D M d Y H:i:s e+', $request->get('end_date'));
            $end_date = $end->format('Y-m-d');
        }else{
            $end_date = date("Y-m-d", strtotime($request->get('end_date')));
        }
        $country = $request->get('country_name');
        if($country == 'null'){
            $country = null;
        }   
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
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('created_at', 'DESC')->get();
                
        } // if country is not null or all and date is null or 1970
        else if((!is_null($country) || $country != null) && $country !== 'all' && ($start_date == '1970-01-01' || $start_date == '') && ($end_date == '1970-01-01' || $end_date == '')) {
            $country = ucwords(str_replace("-", " ", $country));
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->whereCountry($country)->orderBy('created_at', 'DESC')->get();
                
        } // if country is all and date is not null or 1970
        else if((!is_null($country) || $country != null) && $country == 'all' && ($start_date != '1970-01-01' && $start_date != '') && ($end_date != '1970-01-01' && $end_date != '')) {
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('created_at', 'DESC')->get();
                
        } //if country is not null nor all and date is null or 1970
        else if((!is_null($country) || $country != null) && $country == 'all' && ($start_date == '1970-01-01' || $start_date == '') && ($end_date == '1970-01-01' || $end_date == '')) {
                $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->get();
                
        } //if country is not null nor all and date is not null or 1970
        else if((!is_null($country) || $country != null) && $country != 'all' && ($start_date != '1970-01-01' || $start_date != '') && ($end_date != '1970-01-01' || $end_date != '')) {
            $country = ucwords(str_replace("-", " ", $country));
            $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->whereCountry($country)->orderBy('created_at', 'DESC')->get();
                
        }
        else{
            $logs = CookieXrayConsentLog::where('dom_id', '=', $domain_id)->orderBy('created_at', 'DESC')->get();
    }
        if ($logs->count() > 0) {
            $file = 'Cookie Consent Log.csv';
            $filePath = storage_path('download/consent-log/'. $file);
            if (file_exists($filePath)) {
                $output = fopen($filePath, "w");
                fputcsv($output, array(
                    'Sr.#', 'IP Address', 'Country', 'Preference', 'Marketing', 'Statistics', 'Sell my Data?', 'Date And Time',
                    'User ID'
                ));
                $counter = 1;
                foreach ($logs as $item) {
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

    private function getCountryCode(){
    return [
            'Afghanistan' => 'AF',
            'Aland Islands' => 'AX',
            'Albania' => 'AL',
            'Algeria' => 'DZ',
            'American Samoa' => 'AS',
            'Andorra' => 'AD',
            'Angola' => 'AO',
            'Anguilla' => 'AI',
            'Antarctica' => 'AQ',
            'Antigua And Barbuda' => 'AG',
            'Argentina' => 'AR',
            'Armenia' => 'AM',
            'Aruba' => 'AW',
            'Australia' => 'AU',
            'Austria' => 'AT',
            'Azerbaijan' => 'AZ',
            'Bahamas' => 'BS',
            'Bahrain' => 'BH',
            'Bangladesh' => 'BD',
            'Barbados' => 'BB',
            'Belarus' => 'BY',
            'Belgium' => 'BE',
            'Belize' => 'BZ',
            'Benin' => 'BJ',
            'Bermuda' => 'BM',
            'Bhutan' => 'BT',
            'Bolivia' => 'BO',
            'Bosnia And Herzegovina' => 'BA',
            'Botswana' => 'BW',
            'Bouvet Island' => 'BV',
            'Brazil' => 'BR',
            'British Indian Ocean Territory' => 'IO',
            'Brunei Darussalam' => 'BN',
            'Bulgaria' => 'BG',
            'Burkina Faso' => 'BF',
            'Burundi' => 'BI',
            'Cambodia' => 'KH',
            'Cameroon' => 'CM',
            'Canada' => 'CA',
            'Cape Verde' => 'CV',
            'Cayman Islands' => 'KY',
            'Central African Republic' => 'CF',
            'Chad' => 'TD',
            'Chile' => 'CL',
            'China' => 'CN',
            'Christmas Island' => 'CX',
            'Cocos (Keeling) Islands' => 'CC',
            'Colombia' => 'CO',
            'Comoros' => 'KM',
            'Congo' => 'CG',
            'Congo, Democratic Republic' => 'CD',
            'Cook Islands' => 'CK',
            'Costa Rica' => 'CR',
            'Cote D\'Ivoire' => 'CI',
            'Croatia' => 'HR',
            'Cuba' => 'CU',
            'Cyprus' => 'CY',
            'Czech Republic' => 'CZ',
            'Denmark' => 'DK',
            'Djibouti' => 'DJ',
            'Dominica' => 'DM',
            'Dominican Republic' => 'DO',
            'Ecuador' => 'EC',
            'Egypt' => 'EG',
            'El Salvador' => 'SV',
            'Equatorial Guinea' => 'GQ',
            'Eritrea' => 'ER',
            'Estonia' => 'EE',
            'Ethiopia' => 'ET',
            'Falkland Islands (Malvinas)' => 'FK',
            'Faroe Islands' => 'FO',
            'Fiji' => 'FJ',
            'Finland' => 'FI',
            'France' => 'FR',
            'French Guiana' => 'GF',
            'French Polynesia' => 'PF',
            'French Southern Territories' => 'TF',
            'Gabon' => 'GA',
            'Gambia' => 'GM',
            'Georgia' => 'GE',
            'Germany' => 'DE',
            'Ghana' => 'GH',
            'Gibraltar' => 'GI',
            'Greece' => 'GR',
            'Greenland' => 'GL',
            'Grenada' => 'GD',
            'Guadeloupe' => 'GP',
            'Guam' => 'GU',
            'Guatemala' => 'GT',
            'Guernsey' => 'GG',
            'Guinea' => 'GN',
            'Guinea-Bissau' => 'GW',
            'Guyana' => 'GY',
            'Haiti' => 'HT',
            'Heard Island & Mcdonald Islands' => 'HM',
            'Holy See (Vatican City State)' => 'VA',
            'Honduras' => 'HN',
            'Hong Kong' => 'HK',
            'Hungary' => 'HU',
            'Iceland' => 'IS',
            'India' => 'IN',
            'Indonesia' => 'ID',
            'Iran, Islamic Republic Of' => 'IR',
            'Iraq' => 'IQ',
            'Ireland' => 'IE',
            'Isle Of Man' => 'IM',
            'Israel' => 'IL',
            'Italy' => 'IT',
            'Jamaica' => 'JM',
            'Japan' => 'JP',
            'Jersey' => 'JE',
            'Jordan' => 'JO',
            'Kazakhstan' => 'KZ',
            'Kenya' => 'KE',
            'Kiribati' => 'KI',
            'Korea' => 'KR',
            'Kuwait' => 'KW',
            'Kyrgyzstan' => 'KG',
            'Lao People\'s Democratic Republic' => 'LA',
            'Latvia' => 'LV',
            'Lebanon' => 'LB',
            'Lesotho' => 'LS',
            'Liberia' => 'LR',
            'Libyan Arab Jamahiriya' => 'LY',
            'Liechtenstein' => 'LI',
            'Lithuania' => 'LT',
            'Luxembourg' => 'LU',
            'Macao' => 'MO',
            'Macedonia' => 'MK',
            'Madagascar' => 'MG',
            'Malawi' => 'MW',
            'Malaysia' => 'MY',
            'Maldives' => 'MV',
            'Mali' => 'ML',
            'Malta' => 'MT',
            'Marshall Islands' => 'MH',
            'Martinique' => 'MQ',
            'Mauritania' => 'MR',
            'Mauritius' => 'MU',
            'Mayotte' => 'YT',
            'Mexico' => 'MX',
            'Micronesia, Federated States Of' => 'FM',
            'Moldova' => 'MD',
            'Monaco' => 'MC',
            'Mongolia' => 'MN',
            'Montenegro' => 'ME',
            'Montserrat' => 'MS',
            'Morocco' => 'MA',
            'Mozambique' => 'MZ',
            'Myanmar' => 'MM',
            'Namibia' => 'NA',
            'Nauru' => 'NR',
            'Nepal' => 'NP',
            'Netherlands' => 'NL',
            'Netherlands Antilles' => 'AN',
            'New Caledonia' => 'NC',
            'New Zealand' => 'NZ',
            'Nicaragua' => 'NI',
            'Niger' => 'NE',
            'Nigeria' => 'NG',
            'Niue' => 'NU',
            'Norfolk Island' => 'NF',
            'Northern Mariana Islands' => 'MP',
            'Norway' => 'NO',
            'Oman' => 'OM',
            'Pakistan' => 'PK',
            'Palau' => 'PW',
            'Palestinian Territory, Occupied' => 'PS',
            'Panama' => 'PA',
            'Papua New Guinea' => 'PG',
            'Paraguay' => 'PY',
            'Peru' => 'PE',
            'Philippines' => 'PH',
            'Pitcairn' => 'PN',
            'Poland' => 'PL',
            'Portugal' => 'PT',
            'Puerto Rico' => 'PR',
            'Qatar' => 'QA',
            'Reunion' => 'RE',
            'Romania' => 'RO',
            'Russian Federation' => 'RU',
            'Rwanda' => 'RW',
            'Saint Barthelemy' => 'BL',
            'Saint Helena' => 'SH',
            'Saint Kitts And Nevis' => 'KN',
            'Saint Lucia' => 'LC',
            'Saint Martin' => 'MF',
            'Saint Pierre And Miquelon' => 'PM',
            'Saint Vincent And Grenadines' => 'VC',
            'Samoa' => 'WS',
            'San Marino' => 'SM',
            'Sao Tome And Principe' => 'ST',
            'Saudi Arabia' => 'SA',
            'Senegal' => 'SN',
            'Serbia' => 'RS',
            'Seychelles' => 'SC',
            'Sierra Leone' => 'SL',
            'Singapore' => 'SG',
            'Slovakia' => 'SK',
            'Slovenia' => 'SI',
            'Solomon Islands' => 'SB',
            'Somalia' => 'SO',
            'South Africa' => 'ZA',
            'South Georgia And Sandwich Isl.' => 'GS',
            'Spain' => 'ES',
            'Sri Lanka' => 'LK',
            'Sudan' => 'SD',
            'Suriname' => 'SR',
            'Svalbard And Jan Mayen' => 'SJ',
            'Swaziland' => 'SZ',
            'Sweden' => 'SE',
            'Switzerland' => 'CH',
            'Syrian Arab Republic' => 'SY',
            'Taiwan' => 'TW',
            'Tajikistan' => 'TJ',
            'Tanzania' => 'TZ',
            'Thailand' => 'TH',
            'Timor-Leste' => 'TL',
            'Togo' => 'TG',
            'Tokelau' => 'TK',
            'Tonga' => 'TO',
            'Trinidad And Tobago' => 'TT',
            'Tunisia' => 'TN',
            'Turkey' => 'TR',
            'Turkmenistan' => 'TM',
            'Turks And Caicos Islands' => 'TC',
            'Tuvalu' => 'TV',
            'Uganda' => 'UG',
            'Ukraine' => 'UA',
            'United Arab Emirates' => 'AE',
            'United Kingdom' => 'GB',
            'United States' => 'US',
            'United States Outlying Islands' => 'UM',
            'Uruguay' => 'UY',
            'Uzbekistan' => 'UZ',
            'Vanuatu' => 'VU',
            'Venezuela' => 'VE',
            'Viet Nam' => 'VN',
            'Virgin Islands, British' => 'VG',
            'Virgin Islands, U.S.' => 'VI',
            'Wallis And Futuna' => 'WF',
            'Western Sahara' => 'EH',
            'Yemen' => 'YE',
            'Zambia' => 'ZM',
            'Zimbabwe' => 'ZW',
        ];
    }
}
                