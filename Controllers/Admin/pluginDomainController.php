<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PluginOnDomains;
use DataTables;
use Carbon\Carbon;

class pluginDomainController extends Controller
{
    public function index(Request $request){
        $domains = PluginOnDomains::select('*');

        if (isset($_GET['field_value']) && !empty($_GET['field_value'])) {
            if ($_GET['field_value'] == 'wordpress') {
                $platform = $_GET['field_value'];
                $domains->where(['platform'=>$platform]);
            }
            else if ($_GET['field_value'] == 'drupal') {
                $platform = $_GET['field_value'];
                $domains->where(['platform' => $platform]);
            } else if ($_GET['field_value'] == 'joomla') {
                $platform = $_GET['field_value'];
                $domains->where(['platform' => $platform]);
            } else if ($_GET['field_value'] == 'shopify') {
                $platform = $_GET['field_value'];
                $domains->where(['platform' => $platform]);
            } else if ($_GET['field_value'] == 'magento') {
                $platform = $_GET['field_value'];
                $domains->where(['platform' => $platform]);
            } else if ($_GET['field_value'] == 'prestashop') {
                $platform = $_GET['field_value'];
                $domains->where(['platform' => $platform]);
            }
        }

        if (isset($_GET['active_field']) && !empty($_GET['active_field'])) {
            if ($_GET['active_field'] == 'isactive') {
                $isactive = $_GET['active_field'];
                $isactive = 1;
                $domains->where(['isactive' => $isactive]);
            } else if ($_GET['active_field'] == 'inactive') {
                $inactive = $_GET['active_field'];
                $inactive = 0;
                $domains->where('isactive' , $inactive);
            }
        }
        // $domains = $domains->paginate(10);
        if(isset($_GET['start_d']) && isset($_GET['end_d'])){
            if (!empty($request->start_d) && !empty($request->end_d)) {
                $domains->whereBetween('created_at', [$request->start_d . ' 00:00:00', $request->end_d . ' 23:59:59']);
            }
        }
            else{
                $domains->whereDate('created_at', Carbon::today());
            }
            $domains = $domains->orderBy('id', 'desc');
        if($request->ajax()){

            return Datatables::of($domains)
            ->make(true);
        }
        $isactive_wordpress = PluginOnDomains::where(['platform'=>'wordpress'])
        ->where('isactive',1)->count();
        $inactive_wordpress = PluginOnDomains::where(['platform' => 'wordpress'])
        ->where('isactive', 0)->count();
        // dd($isactive_wordpress);
        $isactive_drupal = PluginOnDomains::where(['platform' => 'drupal'])
        ->where('isactive', 1)->count();
        $inactive_drupal = PluginOnDomains::where(['platform' => 'drupal'])
        ->where('isactive', 0)->count();
        // dd($isactive_dupal);
        $isactive_joomla = PluginOnDomains::where(['platform' => 'joomla'])
        ->where('isactive', 1)->count();
        $inactive_joomla = PluginOnDomains::where(['platform' => 'joomla'])
        ->where('isactive', 0)->count();
        // dd($isactive_jumla);
        $isactive_shopify = PluginOnDomains::where(['platform' => 'shopify'])
        ->where('isactive', 1)->count();
        $inactive_shopify = PluginOnDomains::where(['platform' => 'shopify'])
        ->where('isactive', 0)->count();
        // dd($isactive_shopify);
        $isactive_magento = PluginOnDomains::where(['platform' => 'magneto'])
        ->where('isactive', 1)->count();
        $inactive_magento = PluginOnDomains::where(['platform' => 'magento'])
        ->where('isactive', 0)->count();
        // dd($isactive_magneto);
        $isactive_prestashop = PluginOnDomains::where(['platform' => 'prestashop'])
        ->where('isactive', 1)->count();
        $inactive_prestashop = PluginOnDomains::where(['platform' => 'prestashop'])
        ->where('isactive', 0)->count();
        //(for all variable) get_defined_vars()
        return view('admin.plugin_domain.index',compact( 'isactive_wordpress', 'inactive_wordpress', 'isactive_drupal', 'inactive_drupal', 'isactive_joomla', 'inactive_joomla', 'isactive_shopify', 'inactive_shopify', 'isactive_magento', 'inactive_magento', 'isactive_prestashop', 'inactive_prestashop'));
    }
}
