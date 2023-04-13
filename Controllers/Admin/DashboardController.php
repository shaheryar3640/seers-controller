<?php namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Http\Controllers\Admin\AdminController;
/*use App\Article;
use App\ArticleCategory;
use App\User;
use App\Photo;
use App\PhotoAlbum;*/
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Rating;
use App\Models\Setting;
//use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends AdminController {

    public function __construct()
    {
        parent::__construct();
        view()->share('type', '');
    }

	public function index()
	{

        $title = "Dashboard";


		$total_user = User::where('admin','!=','1')
			           ->count();

        $total_seller = User::OnlyAdvisor()
            ->count();

        $total_customer = User::OnlyBusiness()
					   ->count();

		$total_booking = Booking::count();


        $today_booking = Booking::whereDate('booking_date', '=', Carbon::today()->toDateString())->count();

        //dd($today_booking);

		$graph[] = ['label' => Carbon::today()->toDateString(),'y'=> $today_booking];

		for($i=1;$i<7;$i++){
		    $date = Carbon::parse('-' .$i .' day')->toDateString();

		    $graph[] = ['label'=>$date, 'y'=>Booking::whereDate('booking_date', '=', $date)->count()];

        }
        //dd($graph);
        //return response()->json(['total_bookings'=>$total_booking,'graph'=>$graph]);


        $bookings = Booking::orderBy('booking_date','desc')->limit(5)->offset(0)->get();

		$set_id=1;
		$setting = Setting::first();
		$users = User::where(['admin'=>0, 'admin'=>2])->orderBy('id','desc')
            ->limit(4)
            ->get();
        //dd($users[0]->user_type);

		$testimonials = Testimonial::orderBy('id','desc')
				 ->limit(3)->offset(0)
				 ->get();








		$data = array('total_seller' => $total_seller, 'total_user' => $total_user, 'total_customer' => $total_customer, 'total_booking' => $total_booking,
		'today_booking' => $today_booking, 'graph' => $graph, 'bookings' => $bookings, 'setting' => $setting, 'users' => $users,
		'testimonials' => $testimonials);

		return view('admin.index')->with($data);




	}
}
