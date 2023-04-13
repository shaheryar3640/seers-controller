<?php

namespace App\Http\Controllers\Business;

use Auth;
use Exception;
use File;
use Image;
use App\Events\UserHasDeletedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\PartnershipPortal;
use App\Mail\ProfileUpdateConfirmation;
use App\Mail\PartnershipPortalOwner;


class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('business');
        $this->filePath = null;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        if (Auth::User()->isBusiness) {
            return $this->showBusinessDashboard();
        } else if (Auth::User()->isAdvisor) {
            return $this->showAdvisorDashboard();
        }
    }

    public function profile()
    {
        if (Auth::User()->isBusiness) {
            return $this->showBusinessProfile();
        } else if (Auth::User()->isAdvisor) {
            return $this->showAdvisorProfile();
        }
    }

    public function updateBusinessAvatar(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image64:jpeg,jpg,png'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } else {
            $user = Auth::User();
            if ($user->photo != '') {
                File::delete(base_path('images/userphoto/' . $user->photo));
            }
            $imageData = $request->get('avatar');
            $user->photo = uniqid() . '.' . explode('/', explode(':', substr($imageData, 0, strpos($imageData, ';')))[1])[1];
            $user->save();

            $s3Image = Image::make($request->get('avatar'))
                ->resize(200, 200, function ($constraints) {
                    $constraints->aspectRatio();
                });

            Storage::disk('s3')->put('/images/userphoto/' . $user->photo,$s3Image->stream()->__toString(),'public');

            return response()->json(['message' => 'Your new avatar is now updated!', 'avatar_link' => $user->avatar_link, 'error' => false, 'base_path' => base_path('images/userphoto/') . $user->photo]);
        }
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'password' => 'sometimes|min:8',
            'phone' => 'required|min:8',
            // 'job_role' => 'required|string|max:255',
            //            'company' => 'required|string|max:355',
            //            'address' => 'required|string|max:500',
        ], [
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
            /*'phone.required' => 'Phone No is required',
            'job_role.required' => 'Job Role is required',*/
            'company.title.required' => 'Company Name is required',
            'address.required' => 'Address is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => trans('business.profile_update_error')], 400);
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
        if ($request->get('password') != '') {
            $user->password = bcrypt($request->get('password'));
        }
        $user->save();
        // Mail::to($user->email)->send(new ProfileUpdateConfirmation($user));
        // Password Reset | Sendgrid template name
         $to = ['email' => $user->email, 'name' => $user->fname];
        $template = [
            'id' => config('sendgridtemplateid.Password-Reset'), 
            'data' => ['first_name' => $user->fname,'time'=>$user->updated_at]
        ];
        sendEmailViaSendGrid($to, $template);
    }

    public function destroy()
    {
        $user = Auth::user();

        $orginalfile = $user->photo;
        $userphoto = "/userphoto/";
        $path = base_path('images' . $userphoto . $orginalfile);
        File::delete($path);
        if (Storage::disk('s3')->exists('/images/userphoto/' . $orginalfile)) {
            Storage::disk('s3')->delete('/images/userphoto/' . $orginalfile);
        }
        event(new UserHasDeletedEvent($user));

        DB::delete('delete from password_resets where email = ?', [$user->email]);
        $user->is_removed = 1;
        $user->save();
        // \App\User::destroy($user->id);
        Auth::logout();

        return response()->json(['message' => 'Your account has been delete '], 200);

        /*DB::delete('delete from seller_services where user_id = ?',[$userid]);
        DB::delete('delete from ratings where user_id = ?',[$userid]);
        DB::delete('delete from bookings where seller_id = ?',[$userid]);

        DB::delete('delete from shop_gallery where user_id = ?',[$userid]);
        DB::delete('delete from shop where user_id = ?',[$userid]);
        DB::delete('delete from users where id!=1 and id = ?',[$userid]);*/
    }

    public function partnershipPortal()
    {
        $user = Auth::User();
        return view('business.partnership-portal', compact('user'));
    }

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $file_ext = $file->getClientOriginalExtension();
        $name = date('Y-m-d') . time();
        $org_name = $name . '.' . $file_ext;
        $destination = "/storage/template/" . $org_name;
        $this->filePath = $destination;
        if (move_uploaded_file($file, base_path($destination))) {
            return response([
                'file_path' => $destination,
                'extension' => $file_ext
            ]);
        } else {
            return response(['file' => 'not uploaded']);
        }
    }

    public function savePartnershipDetails(Request $request)
    {
        $data = $request->all();
        $path = $data['file_path'];

        if (File::exists(base_path($path))) {
            Mail::to('partners@consents.dev')->bcc(config('app.hubspot_bcc'))->send(new PartnershipPortal(File::get(base_path($path)), $data));
            //  $to = ['email' => 'partners@consents.dev', 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data' => $data]
//         ];
//         sendEmailViaSendGrid($to, $template,File::get(base_path($path)));
            Mail::to($data['email'])->bcc(config('app.hubspot_bcc'))->send(new PartnershipPortalOwner($data));
                  //  $to = ['email' => $data['email'], 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data' => $data]
//         ];
//         sendEmailViaSendGrid($to, $template);

            unlink(base_path($path));
            return response([
                'data'      => $data['file_path'],
                'success'   => 'success'
            ]);
        } else {
            unlink(base_path($path));
            return response([
                'error'   => 'File cannot be empty'
            ], 400);
        }
    }
    public function routeProfile(Request $request)
    {
        session()->put('from_profile', true);
        return redirect(route('business.dashboard'));
    }
    public function routePaymentMethods(Request $request)
    {
        session()->put('from_payment_methods', true);
        return redirect(route('business.dashboard'));
    }
    public function routeGetBusinessOrders(Request $request)
    {
        return response()->json(['business_orders'=>Auth::User()->buyerBookings, 'avatar_link' => Auth::User()->avatar_link]);
    }

}
