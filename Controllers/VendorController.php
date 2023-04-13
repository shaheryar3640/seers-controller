<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;

class VendorController extends Controller
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



    public function sangvish_showpage($id) {

        $uber = DB::table('users')->where('name', '=', $id)->get();
        if(count($uber) < 1)
            return view('errors.404');

        $shopcount = DB::table('shop')
            ->where('seller_email', '=', $uber[0]->email)
            ->count();





        $shop = DB::table('shop')
            ->where('seller_email', '=', $uber[0]->email)
            ->get();


        if($shop[0]->start_time > 12)
        {
            $start=$shop[0]->start_time - 12;
            $stime=$start."PM";
        }
        else
        {
            $stime=$shop[0]->start_time."AM";
        }
        if($shop[0]->end_time>12)
        {
            $end=$shop[0]->end_time-12;
            $etime=$end."PM";
        }
        else
        {
            $etime=$shop[0]->end_time."AM";
        }

        $sel=explode(",",$shop[0]->shop_date);
        $lev=count($sel);


        $viewservice = DB::table('seller_services')
            ->where('shop_id', $shop[0]->id)
            ->orderBy('id','desc')
            ->leftJoin('subservices', 'subservices.id', '=', 'seller_services.subservice_id')
            ->get();

        $set_id=1;
        $setting = DB::table('settings')->where('id', $set_id)->get();

        $viewgallery = DB::table('shop_gallery')->where('shop_id', $shop[0]->id)->get();

        $shop_id = $shop[0]->id;
        $vendor_email=$shop[0]->seller_email;

        $vendor=$shop[0]->id;


        $siteid=1;
        $site_setting=DB::select('select * from settings where id = ?',[$siteid]);

        $userid=$uber[0]->id;



        $rating_count = DB::table('rating')
            ->where('rshop_id', '=', $shop_id)

            ->count();

        $rating = DB::table('rating')
            ->leftJoin('users', 'users.email', '=', 'rating.email')
            ->where('rshop_id', '=', $shop_id)

            ->get();



        $aid=1;
        $admindetails = DB::table('users')
            ->where('id', '=', $aid)
            ->first();

        $admin_email = $admindetails->email;


        $data = array('shopcount' => $shopcount, 'shop' => $shop, 'stime' => $stime, 'etime' => $etime, 'lev' => $lev, 'sel' => $sel, 'viewservice' => $viewservice,
            'setting' => $setting, 'viewgallery' => $viewgallery, 'shop_id' => $shop_id, 'vendor_email' => $vendor_email , 'site_setting' => $site_setting, 'vendor' => $vendor,
            'userid' => $userid, 'rating_count' => $rating_count, 'rating' => $rating,'admin_email' => $admin_email);
        return view('old.vendor')->with($data);
    }



    protected function sangvish_savedata(Request $request)
    {



        $data = $request->all();

        $name=$data['name'];
        $email=$data['email'];
        $phone_no=$data['phone_no'];
        $msg=$data['message'];

        $site_logo=$data['site_logo'];

        $site_name=$data['site_name'];

        $vendor_id=$data['vendor_id'];



        $cnt = DB::table('contact_vendor')
            ->where('email', '=', $email)
            ->where('vendor_id', '=', $vendor_id)
            ->count();





        if($cnt==0)
        {
            DB::insert('insert into contact_vendor (name,phone_no,email,message,vendor_id) values (?, ? , ?, ?, ?)',[$name,$phone_no,$email,$msg,$vendor_id]);


            Mail::send('vendoremail', ['name' => $name, 'email' => $email, 'phone_no' => $phone_no, 'msg' => $msg,
                'site_logo' => $site_logo, 'site_name' => $site_name], function ($message)
            {
                $message->subject('New Enquiry Received');

                $message->from(Input::get('admin_email'), 'Admin');

                /*$message->from(Input::get('email'), Input::get('name'));*/

                $message->bcc(config('app.hubspot_bcc'));
                $message->to(Input::get('vendor_email'));

            });
            //  $to = ['email' => Input::get('vendor_email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['name' => $name, 'email' => $email, 'phone_no' => $phone_no, 'msg' => $msg,            'site_logo' => $site_logo, 'site_name' => $site_name]
//         ];
//         sendEmailViaSendGrid($to, $template);

        }
        else
        {
            return redirect()->back()->with('message', 'Your message already exists');
        }

        return redirect()->back()->with('message', 'Message has been sent successfully');


    }










}
