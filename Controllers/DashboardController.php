<?php

namespace App\Http\Controllers;

use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

use File;
use Image;
use Socialite;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

    }
    private $types = [0 => 'buyer', 2=>'seller'];

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(){
        if(Auth::User()->isBusiness){
            return $this->showBusinessDashboard();
        } else if(Auth::User()->isAdvisor){
            return $this->showAdvisorDashboard();
        }
    }

    public function profile(){
        if(Auth::User()->isBusiness){
            return $this->showBusinessProfile();
        } else if(Auth::User()->isAdvisor){
            return $this->showAdvisorProfile();
        }
    }

    public function updateUserAvatar(Request $request){
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image64:jpeg,jpg,png'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        } else {
            $user = Auth::User();
            if($user->photo != ''){
                File::delete(base_path('images/userphoto/' . $user->photo));
            }
            $imageData = $request->get('avatar');
            $user->photo = uniqid() . '.' . explode('/', explode(':', substr($imageData, 0, strpos($imageData, ';')))[1])[1];
            $user->save();

            Image::make($request->get('avatar'))->save(base_path('images/userphoto/').$user->photo);

            return response()->json(['message'=>'Your new avatar is now updated!','avatar_link'=>$user->avatar_link,'error'=>false,'base_path'=>base_path('images/userphoto/').$user->photo]);
        }
    }

    public function updateAdvisorProfile(Request $request){
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'password' => 'min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()?_-])[A-Za-z\d!@#$%^&*()?_-]{8,}$/',
            'phone' => 'required|string|max:11|min:11',
            'linkurl' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'street_number' => 'required|string|max:500',
            'route' => 'required|string|max:500',
            'locality' => 'required|string|max:500',
            'state' => 'required|string|max:500',
            'postal_code' => 'required|required|string|max:10|min:4',
            'country' => 'required|required|string|max:500',
        ],[
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
            'phone.required' => 'Phone No is required',
            'linkurl' =>  'Link Url is required',
            'address' =>  'Address is required',
            'street_number' =>  'Street Number is required',
            'route' =>  'Route is required',
            'locality' =>  'City is required',
            'state' =>  'State is required',
            'postal_code' =>  'Postal Code is required',
            'country' =>  'Country is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('auth.business_register_error')],400);
        }
        $user = Auth::User();
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->phone = $request->get('phone');
        if($request->get('password') != ''){
            $user->password = bcrypt($request->get('password'));
        }
        $user->linkurl = $request->get('linkedin').$request->get('linkurl');
        $user->address = $request->get('address');
        $user->street_number = $request->get('street_number');
        $user->route = $request->get('route');
        $user->locality = $request->get('locality');
        $user->state = $request->get('state');
        $user->postal_code = $request->get('postal_code');
        $user->country = $request->get('country');
        $user->qualification = $request->get('qualification');
        if(is_array($request->get('days'))){
            $shop = $user->shop;
            $shop->shop_date = implode(',',$request->get('days'));
            $user->shop()->save($shop);
        }

        $user->save();
        return response()->json(['message'=>'Your profile is updated!']);
    }

    public function RegisterBusiness(Request $request){

        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'password' => 'min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()?_-])[A-Za-z\d!@#$%^&*()?_-]{8,}$/',
            'phone' => 'required|string|max:11|min:11',
            'job_role' => 'required|string|max:255',
            'company' => 'required|string|max:355',
            'address' => 'required|string|max:500',
        ],[
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
            'phone.required' => 'Phone No is required',
            'job_role.required' => 'Job Role is required',
            'company.required' => 'Company Name is required',
            'address.required' => 'Address is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('auth.business_profile_update_error')],400);
        }


        $user = Auth::User();
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->email = $request->get('email');
        $user->company = $request->get('company');
        $user->phone = $request->get('phone');
        $user->job_role = $request->get('job_role');
        $user->address = $request->get('address');
        $user->business = true;
        if($request->get('password') != '') {
            $user->password = bcrypt($request->get('password'));
        }
        $user->save();

    }

    //admin=2
    public function showAdvisorProfile(){
        return view('profile');
    }
    //admin=0
    public function showBusinessProfile(){
        $subservices = DB::table('subservices')->get();
        $subservices = end($subservices);
        $userid = Auth::user()->id;
        $check = DB::select('select admin from users where id = ?',[$userid]);

        $editprofile = DB::table('users')
            ->where('users.id', $userid)
            ->get();
        $data = array('editprofile' => $editprofile);
        //return view('business-account-profile')->with($data);
    }

    protected function edituserdata(array $data)
    {
        if($data['admin'] != 0 && $data['admin'] !=2 )
            $data['admin'] = 0;
        switch($data['admin']){
            case '0':
                return $this->editBusinessprofile($data);
                break;
            case '2':
                return $this->editAdvisorprofile($data);
                break;
            default:
                return false;
        }
    }
    protected function editBusinessprofile(Request $request){

        $data = $request->all();
        $id = $data['id'];

        $rules = array(
            'photo' => 'image|mimes:jpeg,png,jpg,gif|max:1024',
            'phoneno' => 'required|numeric',
        );

        $messages = array(
            'phone' => 'Select valid phone number'
        );

        $validator = Validator::make(Input::all(), $rules, $messages);

        if ($validator->fails()) {
            $failedRules = $validator->failed();

            return back()->withErrors($validator);
        }


        $fname = $data['fname'];
        $lname = $data['lname'];
        $company = $data['company'];
        $phone = $data['phoneno'];
        $job_role = $data['job_role'];
        $address = $data['address'];

        $currentphoto = $data['currentphoto'];

        $image = Input::file('photo');
        if ($image != "") {
            $userphoto = "/userphoto/";
            $delpath = base_path('images' . $userphoto . $currentphoto);
            File::delete($delpath);
            $filename = time() . '.' . $image->getClientOriginalExtension();

            $path = base_path('images' . $userphoto . $filename);


            try {
                Image::make($image->getRealPath())->resize(200, 200)->save($path);
            } catch (Exception $e) {
                return back()->withErrors(['photo' => $e->getMessage()]);
            }
            $savefname = $filename;
        } else {
            $savefname = $currentphoto;
        }


        if ($data['password'] != "") {
            $passtxt = bcrypt($data['password']);
        } else {
            $passtxt = $data['savepassword'];
        }

        $admin = $data['usertype'];

        DB::update('update users set fname="' . $fname . '", lname="' . $lname . '", phone="' . $phone . '",password="' . $passtxt . '",company="' . $company . '",job_role="' . $job_role . '",address="' . $address . '", photo="' . $savefname . '",admin="' . $admin . '" where id = ?', [$id]);


        return back()->with('success', 'Account has been updated');


    }

	public function logout()
	{
	    Auth::logout();

	    session()->flush();

        return redirect('/');
	}
	
	
	public function sangvish_deleteaccount()
	{
		$userid = Auth::user()->id;


		$userdetails = DB::table('users')
		 ->where('id', '=', $userid)
		 ->get();
	  
	 $uemail = $userdetails[0]->email;
		
		
		DB::delete('delete from seller_services where user_id = ?',[$userid]);
        DB::delete('delete from rating where email = ?',[$uemail]);
        DB::delete('delete from booking where user_id = ?',[$userid]);
	  
	    DB::delete('delete from shop_gallery where user_id = ?',[$userid]);
	    DB::delete('delete from shop where user_id = ?',[$userid]);
		
		
		DB::delete('delete from users where id!=1 and id = ?',[$userid]);
		return back();
	}


    /**** update Buyer profile data *****/

    protected function edituserdataBuyer(Request $request){
         
          $data = $request->all();

          $id = $data['id'];

          $rules = array(
              'photo' => 'image|mimes:jpeg,png,jpg,gif|max:1024',
              'phoneno' => 'required|numeric',
          );

          $messages = array(
              'phone' => 'Select valid phone number'
          );

          $validator = Validator::make(Input::all(), $rules, $messages);

          if ($validator->fails()) {
              $failedRules = $validator->failed();

              return back()->withErrors($validator);
          }


          $fname = $data['fname'];
          $lname = $data['lname'];
          $company = $data['company'];
          $phone = $data['phoneno'];
          $job_role = $data['job_role'];
          $address = $data['address'];
          $password = bcrypt($data['password']);
          $currentphoto = $data['currentphoto'];

          $image = Input::file('photo');
          if ($image != "") {
              $userphoto = "/userphoto/";
              $delpath = base_path('images' . $userphoto . $currentphoto);
              File::delete($delpath);
              $filename = time() . '.' . $image->getClientOriginalExtension();

              $path = base_path('images' . $userphoto . $filename);


              try {
                  Image::make($image->getRealPath())->resize(200, 200)->save($path);
              } catch (Exception $e) {
                  return back()->withErrors(['photo' => $e->getMessage()]);
              }
              $savefname = $filename;
          } else {
              $savefname = $currentphoto;
          }


          if ($data['password'] != "") {
              $passtxt = $password;
          } else {
              $passtxt = $data['savepassword'];
          }

          $admin = $data['usertype'];

          DB::update('update users set fname="' . $fname . '", lname="' . $lname . '", phone="' . $phone . '",password="' . $passtxt . '",company="' . $company . '",job_role="' . $job_role . '",address="' . $address . '", photo="' . $savefname . '",admin="' . $admin . '" where id = ?', [$id]);


          return back()->with('success', 'Account has been updated');

    }

    public function showBusinessDashboard(){
        $editprofile = DB::table('users')
            ->where('users.id', Auth::user()->id)
            ->get();
        $data = array('editprofile' => $editprofile);
        return view('dashboard-business')->with($data);
    }

    public function showAdvisorDashboard(){
        $editprofile = DB::table('users')
            ->where('users.id', Auth::user()->id)
            ->get();
        $data = array('editprofile' => $editprofile);
        return view('dashboard-advisor')->with($data);
    }
    public function routeBookingSuccess() {
        return view('business.booking_success');
    }
}