<?php

namespace App\Http\Controllers\Admin;



use App\Models\Service;
use App\Models\SubService;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class SubservicesController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        $subservices = SubService::all();

        return view('admin.subservices.show')->with(['subservices' => $subservices]);
    }

    public function store(Request $request)
    {
        $rules = [
            'subname' => 'required|unique:subservices,subname',
            'photo' => 'image|mimes:jpeg,png,jpg|max:2048'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
        {
            //$failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $data = $request->all();
        $image = $request->file('photo');
        if($image!="")
        {
            $filename  = uniqid() . '.' . $image->getClientOriginalExtension();
            $userphoto="/subservicephoto/";
            $path = base_path('images'.$userphoto.$filename);
            //$destinationPath=base_path('images'.$userphoto);


            Image::make($image->getRealPath())->resize(300, 300)->save($path);
            /*Input::file('photo')->move($destinationPath, $filename);*/
            /* $user->image = $filename;
             $user->save();*/
            $data['subimage'] = $filename;
        }
        SubService::create($data);

        return back()->with('success', 'Sub service has been created');

    }

    public function update($id, Request $request)
    {
        $subservice = SubService::find($id);
        if($subservice == null)
        {
            return back()->with(['errors'=>['subservice'=>'Subservice Not Found']]);
        }
        $rules = [
            'subname' => 'required',
            'photo' => 'image|mimes:jpeg,png,jpg|max:2048'
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
        {
            //$failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $subservice->fill($request->all());


        $image = $request->file('photo');
        if($image!="")
        {
            $subservicephoto="/subservicephoto/";

            File::delete($subservice->image_path);
            $filename  = time() . '.' . $image->getClientOriginalExtension();

            $path = base_path('images'.$subservicephoto.$filename);
            $destinationPath=base_path('images'.$subservicephoto);

            Image::make($image->getRealPath())->resize(200, 200)->save($path);
            $subservice->subimage = $filename;
        }
        $subservice->save();

        return back()->with('success', 'Sub service has been updated');
    }

	public function destroy($id) {
		$subservice = SubService::find($id);


	  File::delete($subservice->image_path);
      //$subservice->delete();
        if($subservice != null){
            SubService::destroy($subservice->id);
        }
	   
      return back();
      
   }
   public function routeAddSubService()
	{
        $services = Service::all();
		return view('admin.subservices.create', compact('services'));		
	}
   public function routeEditSubservice($id)
	{
		$services = Service::all();
        $subservice = SubService::find($id);
        return view('admin.subservices.edit')->with(['subservice'=>$subservice,'services' => $services]);		
	}
	
}