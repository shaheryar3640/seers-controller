<?php

namespace App\Http\Controllers\Admin;

use App\Models\Defaulter;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class defaulterListController extends Controller
{
    public function index(Request $request){
        $data=Defaulter::select('*');
        if(isset($_GET['start_d']) && isset($_GET['end_d'])){
            if (!empty($_GET['start_d']) && !empty($_GET['end_d'])) {
                $data->whereBetween('created_at', [$_GET['start_d'] . ' 00:00:00', $_GET['end_d'] . ' 23:59:59']);
            }
        }
        else{
            $data = $data->whereDate('created_at', Carbon::now());
        }
        if ($request->ajax()) {
            return Datatables::of($data)
                    ->make(true);
        }
        return view('admin.defaulterList.index');
    }
}

