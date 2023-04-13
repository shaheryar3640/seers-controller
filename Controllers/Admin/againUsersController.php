<?php

namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class againUsersController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $plan_names = Plan::where('product_id',2)->select('name')->pluck('name')->unique();

        $users = User::with('userDomains')->where('email','not like','%seersco.com%')->with('cmp_product.plan')->has('cmp_product.plan')->select('*');
        
        if (isset($_GET['verification']) && $_GET['verification'] == 'verified') {
            $users = $users->where('is_verified', 1);
        }
        elseif(isset($_GET['verification']) && $_GET['verification'] == 'unverified'){
            $users = $users->where('is_verified', 0);

        }
        if (isset($_GET['type']) && $_GET['type'] == 'appsumo') {
            $type = 'appsumo';
            $users = $users->where('type', $type);
        }
        elseif(isset($_GET['type']) && $_GET['type'] == 'user'){
            $type = 'appsumo';
            $users = $users->where(function($q) {
                $q->whereNull('type')->orWhere('type','=','');
            });

        }
        if (isset($_GET['plan_name']) && !empty($_GET['plan_name'])) {
            $plan_name = $_GET['plan_name'];
            $users->whereHas('cmp_product.plan', function ($q) use ($plan_name) {
                $q->where('name', '=', $plan_name);
            });
        }
        if (isset($_GET['start_d']) && isset($_GET['end_d']) && !empty($request->start_d) && !empty($request->end_d)) {
            if (!empty($request->start_d) && !empty($request->end_d)) {
                $users = $users->whereBetween('created_at', [$request->start_d. ' 00:00:00', $request->end_d. ' 23:59:59']);
            }
        }
        else
        {
            $users = $users->whereDate('created_at', Carbon::today());
        }
        $users = $users->orderBy('id', 'desc')->paginate(10)->appends(request()->query());
        return view('admin.againuserlist.users',compact('users','plan_names'));
    }
}
