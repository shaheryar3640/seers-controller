<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DataTables;
use App\Models\Invoice;
use App\Models\Product;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request){
        $product_names = Product::orderBy('sort_order','asc')->select('id','name','display_name')->where(['is_active' => 1])->get();
        $currencies = Invoice::groupby('currency')->pluck('currency')->toArray();

        $currencies = array_flip($currencies);

        $currencies = array_map(function($item) { return 0; }, $currencies);
        // dd($currencies);
        $data = Invoice::select('*');
        if(isset($_GET['start_d']) && isset($_GET['end_d'])){
            if (!empty($_GET['start_d']) && !empty($_GET['end_d'])) {

                $data->whereBetween('created_at', [$_GET['start_d'] . ' 00:00:00', $_GET['end_d'] . ' 23:59:59']);
            }
        }
        if (isset($_GET['product']) && !empty($_GET['product'])) {
            $product = $_GET['product'];
            $data = $data->where('product',$product);
        }
        // else{
        //     $data = Invoice::select("*")->whereDate('created_at', Carbon::today());
        // }
        // ->paginate(10)->appends(request()->query())
        $data = $data->orderBy('id', 'desc')->get();
        return view('admin.invoice.index',compact('data','product_names','currencies'));
    }
}
