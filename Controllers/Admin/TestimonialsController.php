<?php

namespace App\Http\Controllers\Admin;



use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\User;
use App\Testimonial;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class TestimonialsController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
        //$testimonials = DB::table('testimonials')->orderBy('id','desc')->get();
        $testimonials = Testimonial::orderBy('id','desc')->get();

        return view('admin.testimonials', ['testimonials' => $testimonials]);
    }
	
	
	public function destroy($id) {
		
		//$image = DB::table('testimonials')->where('id', $id)->first();
        $testimonial = Testimonial::find($id);

		$orginalfile=$testimonial->image;
		$testimonialphoto="/testimonialphoto/";
        $path = base_path('images'.$testimonialphoto.$orginalfile);
	    File::delete($path);
        //DB::delete('delete from testimonials where id = ?',[$id]);
        //$testimonial->delete();
        if($testimonial != null){
            Testimonial::destroy($testimonial->id);
        }
	   
      return back();
      
   }
	
}