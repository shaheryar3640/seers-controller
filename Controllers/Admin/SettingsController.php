<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use File;
use Image;
use Mail;
use App\Mail\MasterKeyMail;
use App\Models\Setting;
// use Symfony\Component\Console\Input\Input;

class SettingsController extends Controller
{


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
    }


    public function showform()
    {
        
        //$settings = DB::select('select * from settings where id = ?', [1]);

        //$settings = Setting::find(1);
        $settings = Setting::where('id', 1)->get();

        $currency = array("USD", "CZK", "DKK", "HKD", "HUF", "ILS", "JPY", "MXN", "NZD", "NOK", "PHP", "PLN", "SGD", "SEK", "CHF", "THB", "AUD", "CAD", "EUR", "GBP", "AFN", "DZD",
            "AOA", "XCD", "ARS", "AMD", "AWG", "SHP", "AZN", "BSD", "BHD", "BDT", "INR");

        $withdraw = array("paypal", "bank");

        $data = array('settings' => $settings, 'currency' => $currency, 'withdraw' => $withdraw);
        return view('admin.settings')->with($data);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users'

        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return User
     */

    protected $fillable = ['name', 'email', 'password', 'phone'];

    protected function editsettings(Request $request)
    {
        


        $data = $request->all();


        $site_name = $data['site_name'];


        $currency = $data['currency'];


        $rules = array(

            'site_logo' => 'max:1024|mimes:jpg,jpeg,png',
            'site_favicon' => 'max:1024|mimes:jpg,jpeg,png',
            'site_banner' => 'max:1024|mimes:jpg,jpeg,png'


        );

        $messages = array();
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $failedRules = $validator->failed();

            return back()->withErrors($validator);
        } else {

            $currentlogo = $data['currentlogo'];
            $image = $request->file('site_logo');
            if ($image != "") {
                $settingphoto = "/settings/";
                $delpath = base_path('images' . $settingphoto . $currentlogo);
                File::delete($delpath);
                $filename = time() . '.' . $image->getClientOriginalExtension();

                $path = base_path('images' . $settingphoto . $filename);
                $destinationPath = base_path('images' . $settingphoto);

                /*Image::make($image->getRealPath())->resize(200, 200)->save($path);*/

                $request->file('site_logo')->move($destinationPath, $filename);
                $savefname = $filename;
            } else {
                $savefname = $currentlogo;
            }


            $currentfav = $data['currentfav'];


            $images = $request->file('site_favicon');
            if ($images != "") {
                $settingphotos = "/settings/";
                $delpaths = base_path('images' . $settingphotos . $currentfav);
                File::delete($delpaths);
                $filenames = time() . '.' . $images->getClientOriginalExtension();

                $paths = base_path('images' . $settingphotos . $filenames);
                $destinationPaths = base_path('images' . $settingphotos);

                Image::make($images->getRealPath())->resize(24, 24)->save($paths);

                /* Input::file('site_logo')->move($destinationPath, $filename);*/
                $savefav = $filenames;
            } else {
                $savefav = $currentfav;
            }


            $currentban = $data['currentban'];


            $banimages = $request->file('site_banner');
            if ($banimages != "") {
                $settingbanphotos = "/settings/";
                $delpathes = base_path('images' . $settingbanphotos . $currentban);
                File::delete($delpathes);
                $banfilenames = time() . '.' . $banimages->getClientOriginalExtension();

                $banpaths = base_path('images' . $settingbanphotos . $banfilenames);
                $destinationbanPaths = base_path('images' . $settingbanphotos);

                Image::make($banimages->getRealPath())->resize(1920, 500)->save($banpaths);

                /* Input::file('site_logo')->move($destinationPath, $filename);*/
                $savefavs = $banfilenames;
            } else {
                $savefavs = $currentban;
            }


            $site_desc = $data['site_desc'];
            $site_keyword = $data['site_keyword'];


            if ($data['site_desc'] != "") {
                $desctxt = $site_desc;
            } else {
                $desctxt = $data['save_desc'];
            }


            if ($data['site_keyword'] != "") {
                $keytxt = $site_keyword;
            } else {
                $keytxt = $data['save_key'];
            }


            $commission_mode = $data['commission_mode'];
            $commission_amt = $data['commission_amt'];

            $paypal_id = $data['paypal_id'];
            $paypal_url = $data['paypal_url'];

            $withdraw_opt = "";
            foreach ($data['withdraw_opt'] as $with) {
                $withdraw_opt .= $with . ",";
            }
            $withdraw = rtrim($withdraw_opt, ",");

            $withdraw_amt = $data['withdraw_amt'];


            $fb = $data['site_facebook'];

            if ($data['site_facebook'] != "") {
                $facebook = $fb;
            } else {
                $facebook = $data['save_facebook'];
            }

            $twi = $data['site_twitter'];

            if ($data['site_twitter'] != "") {
                $twitter = $twi;
            } else {
                $twitter = $data['save_twitter'];
            }


            $gpl = $data['site_gplus'];

            if ($data['site_gplus'] != "") {
                $gplus = $gpl;
            } else {
                $gplus = $data['save_gplus'];
            }


            $pin = $data['site_pinterest'];

            if ($data['site_pinterest'] != "") {
                $pinterest = $pin;
            } else {
                $pinterest = $data['save_pinterest'];
            }


            $ins = $data['site_instagram'];

            if ($data['site_instagram'] != "") {
                $instagram = $ins;
            } else {
                $instagram = $data['save_instagram'];
            }

            $lin = $data['site_linkedin'];

            if ($data['site_linkedin'] != "") {
                $linkedin = $lin;
            } else {
                $linkedin = $data['save_linkedin'];
            }

            $yt = $data['site_youtube'];

            if ($data['site_youtube'] != "") {
                $youtube = $yt;
            } else {
                $youtube = $data['save_youtube'];
            }

            $copys = $data['site_copyright'];

            if ($data['site_copyright'] != "") {
                $copyrights = $copys;
            } else {
                $copyrights = $data['save_copyright'];
            }

            if ($data['vat'] != "") {
                $vat = $data['vat'];
            } else {
                $vat = 1.2;
            }


            //DB::update('update settings set site_name="' . $site_name . '",site_desc="' . $desctxt . '",site_keyword="' . $keytxt . '",site_linkedin="' . $linkedin . '",site_twitter="' . $twitter . '",site_youtube="' . $youtube . '",site_facebook="' . $facebook . '",site_pinterest="' . $pinterest . '",site_instagram="' . $instagram . '",site_gplus="' . $gplus . '",site_currency="' . $currency . '",site_logo="' . $savefname . '",site_favicon="' . $savefav . '",site_banner="' . $savefavs . '",site_copyright="' . $copyrights . '",commission_mode="' . $commission_mode . '",commission_amt="' . $commission_amt . '", paypal_id="' . $paypal_id . '",paypal_url="' . $paypal_url . '",withdraw_amt="' . $withdraw_amt . '",withdraw_option="' . $withdraw . '" where id = ?', [1]);
            $setting = Setting::find(1);
            $setting->site_name = $site_name;
            $setting->site_desc = $desctxt;
            $setting->site_keyword = $keytxt;
            $setting->site_linkedin = $linkedin;
            $setting->site_twitter = $twitter;
            $setting->site_youtube = $youtube;
            $setting->site_facebook = $facebook;
            $setting->site_pinterest = $pinterest;
            $setting->site_instagram = $instagram;
            $setting->site_gplus = $gplus;
            $setting->site_currency = $currency;
            $setting->site_logo = $savefname;
            $setting->site_favicon = $savefav;
            $setting->site_banner = $savefavs;
            $setting->site_copyright = $copyrights;
            $setting->commission_mode = $commission_mode;
            $setting->commission_amt = $commission_amt;
            $setting->paypal_id = $paypal_id;
            $setting->paypal_url = $paypal_url;
            $setting->withdraw_amt = $withdraw_amt;
            $setting->vat = $vat;
            if($data['master_key'] != null){
            $setting->master_key = Hash::make($data['master_key']);
            }
            $setting->withdraw_option = $withdraw;
            $setting->save();
            if($data['master_key'] != null){
            Mail::send(new MasterKeyMail($data['master_key']));
            //  $to = ['email' => 'usman.ejaz@seersco.com', 'name' => 'Usman Bro'];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['key' => $data['master_key']
//         ];
//         sendEmailViaSendGrid($to, $template);
            }
            return back()->with('success', 'Settings has been updated');


        }


    }
}