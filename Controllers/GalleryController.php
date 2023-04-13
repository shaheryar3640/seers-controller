<?php

namespace App\Http\Controllers;



use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class GalleryController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
	public function __construct()
    {
        $this->middleware('auth');
    }
 
	 
    public function index()
    {
        
		
		
		
		$uuid=Auth::user()->id;
		$uid=Auth::user()->email;
		
		$shopview = DB::table('shop')->where('seller_email','=', $uid)->get();
		
		
		$ccount = DB::table('shop_gallery')
		->where('user_id', $uuid)
		->orderBy('id','desc')
		->count();
		
		$viewgallery = DB::table('shop_gallery')
		->where('user_id', $uuid)
		->orderBy('id','desc')
		->get();
		
		$editid="";
		
		
		$data = array('shopview' => $shopview, 'uuid' => $uuid, 'viewgallery' => $viewgallery, 'editid' => $editid, 'ccount' => $ccount, 'uid' => $uid);

        return view('gallery')->with($data); 
		  
    }
	
	
	public function sangvish_destroy($did) {
		
		$image = DB::table('shop_gallery')->where('id', $did)->get();
		$orginalfile=$image[0]->image;
		$galphoto="/gallery/";
       $path = base_path('images'.$galphoto.$orginalfile);
	  File::delete($path);
      DB::delete('delete from shop_gallery where id = ?',[$did]);
	   
      
	 
	  return redirect('gallery');
      
   }
   
   
   public function sangvish_editdata($id) {
      
		
		$uuid=Auth::user()->id;
		$uid=Auth::user()->email;
		
		$shopview = DB::table('shop')->where('seller_email', $uid)->get();
		
		$ccount = DB::table('shop_gallery')
		->where('user_id', $uuid)
		->orderBy('id','desc')
		->count();
		
		$viewgallery = DB::table('shop_gallery')
		->where('user_id', $uuid)
		->orderBy('id','desc')
		->get();
		
		$editid="";
		
		
		$editgallery = DB::select('select * from shop_gallery where id = ?',[$id]);
		$editid=$id;
	   
      $data = array('shopview' => $shopview, 'uuid' => $uuid, 'viewgallery' => $viewgallery, 'editid' => $editid, 'editgallery' => $editgallery, 'ccount' => $ccount);

        return view('gallery')->with($data); 
   }
   
   
   protected function sangvish_savedata(Request $request)
   {
	   $data = $request->all();
	   
	   
	   $user_id=$data['user_id'];
	   $shop_id=$data['shop_id'];
	   
	   $editid=$data['editid'];
	   
	   
	   
	   $rules = array(
               
		'photo' => 'max:1024|mimes:jpg,jpeg,png'
		
		
        );
		
		$messages = array(
            
            'email' => 'The :attribute field is already exists',
            'name' => 'The :attribute field must only be letters and numbers (no spaces)'
			
        );
		
	
		 $validator = Validator::make(Input::all(), $rules, $messages);

		
	   
	   
	   
	   $photo = Input::file('photo');
	   
	   
	   if ($validator->fails())
		{
			$failedRules = $validator->failed();
			 
			return back()->withErrors($validator);
		}
		else
		{ 
	   
				 if($photo!="")
				 {
					if($editid!="")
					{
					$galleryphoto="/gallery/";
					$delpath = base_path('images'.$galleryphoto.$data['current_photo']);
					File::delete($delpath);
					}
					 
					$filename  = time() . '.' . $photo->getClientOriginalExtension();
					$galleryphoto="/gallery/";
					$path = base_path('images'.$galleryphoto.$filename);
					$destinationPath=base_path('images'.$galleryphoto);
		 
				
					   Image::make($photo->getRealPath())->resize(300, 300)->save($path);
						
						$namef=$filename;
				 }
				 else
				 {
					 $namef=$data['current_photo'];
				 }
			   
	   
	   
	   
	   
			   if($editid=="")
			   {
			   
					   
					   DB::insert('insert into shop_gallery (image,user_id,shop_id) values (?, ?, ?)', [$namef,$user_id,$shop_id]);
						
					   return back()->with('success', 'Image has been added');
					  
					   
			   }
			   else if($editid!="")
			   {
				   DB::update('update shop_gallery set image="'.$namef.'",shop_id="'.$shop_id.'" where id = ?', [$editid]);
					return back()->with('success', 'Image has been updated');
			   }
		}	   
	   
   }
   
   
	
}