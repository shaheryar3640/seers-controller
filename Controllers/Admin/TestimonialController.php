<?php

namespace App\Http\Controllers\Admin;



use App\Models\Testimonial;
use File;
use Illuminate\Http\Resources\Json\Resource;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class TestimonialController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function rules(){
        return [
            'name' => 'required|string|max:255',
            'photo' => 'max:1024|mimes:jpg,jpeg,png'

        ];
    }

    public function messages(){
        return [];
    }

    public function validator(array $data)
    {
        return Validator::make($data, $this->rules(), $this->messages());
    }
    public function index()
    {
        $testimonials = Testimonial::paginate(20);
        

        return view('admin.testimonials.index', ['testimonials' => $testimonials]);
    }

    public function destroy($id) {
        $testimonial = Testimonial::find($id);
        if(!empty($testimonial))
        {
            File::delete($testimonial->image_path);
            //$testimonial->delete();
            if($testimonial != null){
                Testimonial::destroy($testimonial->id);
            }
            return redirect(route('admin.testimonials.index'))->with('success', 'Testimonial deleted!');
        }


        return redirect(route('admin.testimonials.index'))->with('error', 'Testimonial not found!');

    }

    public function create(Request $request){
        return view('admin.testimonials.create');
    }

    public function store(Request $request){
        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $testimonial = Testimonial::create($request->all());
        if($request->hasFile('photo')){

            $image = $request->file('photo');

            $testimonial->image  = uniqid() . '.' . $image->getClientOriginalExtension();
            try {
                Image::make($image->getRealPath())->resize(100, 100)->save($testimonial->image_path);
            } catch (Exception $e) {
                //report($e);

                return back()->with(['errors'=>$e->getMessage()]);
            }



        }
        $testimonial->save();

        return redirect(route('admin.testimonials.index'))->with('success', 'Testimonial created!');
    }

    public function edit($id){
        $testimonial = Testimonial::find($id);
        return view('admin.testimonials.edit',['testimonial'=>$testimonial]);
    }


    public function update($id, Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()){
            $failedRules = $validator->failed();
            return back()->withErrors($validator);
        }

        $testimonial = Testimonial::find($id);
        if($request->hasFile('photo')){
            $image = $request->file('photo');
            File::delete($testimonial->image_path);
            $testimonial->image  = uniqid() . '.' . $image->getClientOriginalExtension();
            try {
                Image::make($image->getRealPath())->resize(100, 100)->save($testimonial->image_path);
            } catch (Exception $e) {
                //report($e);
                return back()->with(['errors'=>$e->getMessage()]);
            }

        }
        $testimonial->fill($request->all());
        $testimonial->save();

        return redirect(route('admin.testimonials.index'))->with('success', 'Testimonial updated!');
    }
}