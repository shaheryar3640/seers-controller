<?php

namespace App\Http\Controllers\Admin;
use DataTables;
use App\Models\Crawlerlogs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class CrawlerlogsController extends Controller
{
    public function index(Request $request){
        $data = Crawlerlogs::select('*');
        if(isset($_GET['start_d']) && isset($_GET['end_d'])){
            if (!empty($_GET['start_d']) && !empty($_GET['end_d'])) {

                $data = $data->whereBetween('created_at', [$_GET['start_d'] . ' 00:00:00', $_GET['end_d'] . ' 23:59:59']);
            }
        }
        else{
            $data = $data->whereDate('created_at', Carbon::today());
        }
        if ($request->ajax()) {
            return Datatables::of($data)
                    ->make(true);
        }
        return view('admin.crawlerlogs.index');
    }
}
