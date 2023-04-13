<?php

namespace App\Http\Controllers\Admin;



use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        //$services = DB::table('services')->orderBy('id','desc')->get();

        $services = Service::orderBy('id','desc')->get();

        return view('admin.services', ['services' => $services]);
    }
	
	
	public function destroy($id) {
		
		//$image = DB::table('services')->where('id', $id)->first();

        $service = Service::find($id);

		$orginalfile=$service->image;
		$userphoto="/servicephoto/";
        $path = base_path('images'.$userphoto.$orginalfile);
	    File::delete($path);
        //DB::delete('delete from services where id = ?',[$id]);
        //$service->delete();

        if($service != null){
            Service::destroy($service->id);
        }
	   
        return back();
   }
	
}