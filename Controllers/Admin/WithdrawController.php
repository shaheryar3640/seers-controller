<?php

namespace App\Http\Controllers\Admin;



use App\Mail\AdvisorWithdrawalCompleted;
use App\Mail\WithdrawalCompleted;
use App\Models\Withdraw;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;
use Mail;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use URL;

class WithdrawController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function pendingIndex()
    {
        $withdrawals = Withdraw::where('status','=', 'pending')->paginate(10);

        //return response()->json($withdrawals);
        //dd($withdrawals);

        /*$withdrawals = DB::table('withdraws')
                            ->leftJoin('users', 'users.id', '=', 'withdraws.seller_id')
                            ->where('withdraws.status','=', 'pending')
                            ->orderBy('withdraws.id','desc')->paginate(10);*/
        //dd($withdrawals);

        return view('admin.withdraw.pending_index')->with(['withdrawals' => $withdrawals]);

    }
	public function completedIndex()
    {
        $withdrawals = Withdraw::where('status','=', 'complete')->paginate(10);

        /*$withdrawals = DB::table('withdraws')
            ->leftJoin('users', 'users.id', '=', 'withdraws.seller_id')
            ->where('withdraws.status','=', 'complete')
            ->orderBy('withdraws.id','desc')
            ->paginate(10); */

        return view('admin.withdraw.complete_index')->with(['withdrawals' => $withdrawals]);

    }

    public function markAsCompleted(Request $request){
       /* var_dump('in function');
        return response()->json(Withdraw::find($request->get('id')));
        dd(Withdraw::find($request->get('id')));*/
        $withdrawal = Withdraw::find($request->get('id'));
        $withdrawal->status = 'complete';
        //dd($withdrawal->seller);
        $withdrawal->save();

        if($withdrawal->seller != null){
            Mail::send(new WithdrawalCompleted($withdrawal));
            // $admin = User::where('admin','=','1')->first();
            //  $to = ['email' => $admin->email, 'name' => $admin->name];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => [ "withdrawal" => $withdrawal,"admin"=>$admin ]
//         ];
//         sendEmailViaSendGrid($to, $template);
            Mail::send(new AdvisorWithdrawalCompleted($withdrawal));
            //  $to = ['email' => $withdrawal->seller->email, 'name' => $withdrawal->seller->name];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => [ "withdrawal" => $withdrawal ]
//         ];
//         sendEmailViaSendGrid($to, $template);
        }

        //return response()->json(['success']);
        $withdrawals = Withdraw::where('status','=', 'pending')->paginate(10);
        return view('admin.withdraw.pending_index')->with(['withdrawals' => $withdrawals]);
    }

}