<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use DataTables;
use Carbon\Carbon;

class StriplogsController extends Controller
{
    public function index(Request $request){
                $data = ActivityLog::select('*');
                if (isset($_GET['type']) && $_GET['type'] == 'recursive') {
                    $type = 'recursive';
                    $data = $data->where('type', $type);
                }
                elseif(isset($_GET['type']) && $_GET['type'] == 'checkout'){
                    $type = 'checkout';
                    $data = $data->where('type', $type);
                }
        if(isset($_GET['start_d']) && isset($_GET['end_d'])){
                $data=$data->whereBetween('created_at', [$_GET['start_d'] . ' 00:00:00', $_GET['end_d'] . ' 23:59:59']);
                // $data=$data->get();
        }
        else{
            $data = $data->whereDate('created_at', Carbon::today());
        }

        if ($request->ajax()) {
            return Datatables::of($data)
                    ->make(true);
        }
        return view('admin.stripactivitylogs.index');
    }
}
