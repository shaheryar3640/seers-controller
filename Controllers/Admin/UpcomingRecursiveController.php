<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UpcomingRecursiveController extends Controller
{
    public function index(Request $request){
        $p = UProduct::whereMonth('created_at',11)->take(4)->get();
        // dd($p);
        $currencySign = UProduct::where('currency','!=','')->pluck('currency')->unique()->toArray();
        $currencySign = array_flip($currencySign);
        $currencySign = array_map(function($item) { return 0; }, $currencySign);
        // dd($currencySign);
        $users = User::where('email','not like','%seersco.com%')->with('cmp_product.plan','recursivePayment')->whereHas('cmp_product.plan',function($q){
            $q->where('expired_on','>=',Carbon::today());
        })->has('recursivePayment')->select('*');
        if (isset($_GET['tenure']) && !empty($_GET['tenure'])) {
                $tenure = $_GET['tenure'];
                $users = $users->whereHas('cmp_product',function($q) use ($tenure){
                    $q->where('recursive_status',$tenure);
                });
        }
        if (isset($_GET['month']) && !empty($_GET['month'])) {
            $month = $_GET['month'];
            // dd($month);
            $users = $users->whereHas('cmp_product.plan',function($q) use ($month){
                $q->whereMonth('expired_on',$month);
            });
        }
        if (isset($_GET['days']) && !empty($_GET['days'])) {
            $days = $_GET['days'];
            // dd($days);
            $users = $users->whereHas('cmp_product.plan',function($q) use ($days){
                $q->whereDay('expired_on',$days);
            });
        }
        $user_widgets = $users->orderBy('id', 'desc')->get();
        
        $users = $users->orderBy('id', 'desc')->paginate(10)->appends(request()->query());
        return view('admin.upcomingRecursive.index', compact('users','currencySign','user_widgets'));
    }
}
