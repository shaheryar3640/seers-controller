<?php

namespace App\Http\Controllers\Admin;



use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Events\UserHasDeletedEvent;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserGuideDownload;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

use App\Models\SellerService;
use App\Models\Rating;
use App\Models\Booking;
use App\Models\ShopGallery;
use App\Models\Shop;


class UsersController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        /*$users = DB::table('users')
            ->orderBy('id','desc')
            ->where('admin',[0])
            ->get(); */
        $filter = $request->query('filter');
        if(!empty($filter)){
            $users = User::where('admin',0)
            ->where('fname','like','%'.$filter.'%')
            ->orWhere('lname','like','%'.$filter.'%')
            ->orWhere('email','like','%'.$filter.'%')
            ->orWhere('phone','like','%'.$filter.'%')
            ->orderBy('id','desc')->paginate(10);
        }else{
            $users = User::where('admin',0)->orderBy('id','desc')->paginate(10);
        }
        //dd($users[0]->user_type);
        return view('admin.users', ['users' => $users,'filter' =>$filter]);
    }

    public function advisors(Request $request)
    {
        /*$users = DB::table('users')
            ->orderBy('id','desc')
            ->where('admin',[2])
            ->get();*/

        $filter = $request->query('filter');
        if(!empty($filter)){
            $users = User::where('admin',2)
            ->where('fname','like','%'.$filter.'%')
            ->orWhere('lname','like','%'.$filter.'%')
            ->orWhere('email','like','%'.$filter.'%')
            ->orWhere('phone','like','%'.$filter.'%')
            ->orderBy('id','desc')->paginate(10);
        }else{
            $users = User::where('admin',2)->orderBy('id','desc')->paginate(10);
        }
        //dd($users[0]->user_type);
        return view('admin.users', ['users' => $users,'filter' =>$filter]);
    }

        // $users = User::where('admin',2)->orderBy('id','desc')->get();

        // return view('admin.users', ['users' => $users,"filters"]);

	public function guides()
    {
        /*$users = DB::table('users')
            ->orderBy('id','desc')
            ->where('admin',[0])
            ->get(); */

        //$users = User::whereNotNull('bookname')->orderBy('id','desc')->get();
        $users = UserGuideDownload::whereNotNull('bookname')->orderBy('id','desc')->get();
        //dd($users[0]->user_type);
        return view('admin.userguide', ['guides' => $users]);
    }

	public function destroy($id) {

        $user = User::find($id);

		$orginalfile = $user->photo;
		$userphoto="/userphoto/";
        $path = base_path('images'.$userphoto.$orginalfile);
	    File::delete($path);

        if ($user->isAdvisor) {
            $seller_services = SellerService::where('user_id', $id)->get();
            foreach ($seller_services as $seller_service){
                SellerService::destroy($seller_service->id);
            }

            $ratings = Rating::where('seller_id', $id)->get();

            foreach($ratings as $rating){
                Rating::destroy($rating->id);
            }
            $bookings = Booking::where('seller_id', $id)->get();

            foreach($bookings as $booking){
                Booking::destroy($booking->id);
            }
        } else {
            $ratings = Rating::where('buyer_id', $id)->get();

            foreach($ratings as $rating){
                Rating::destroy($rating->id);
            }
            $bookings = Booking::where('buyer_id', $id)->get();

            foreach($bookings as $booking){
                Booking::destroy($booking->id);
            }
        }

        event(new UserHasDeletedEvent($user));

        $user->delete();

        return back();

   }

}
