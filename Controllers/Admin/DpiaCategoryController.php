<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DpiaCategory;
use App\Models\DpiaSubCategory;
use App\Models\DpiaLogs;
use Illuminate\Support\Facades\Auth;
// use Auth;

class DpiaCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dpiaCategories = DpiaCategory::all();
        return view('admin.dpia.category.show', compact('dpiaCategories'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.dpia.category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(DpiaCategory::$rules,DpiaCategory::$messages);
        $data = $request->all();

        //dd($data['name']);
        $category = new DpiaCategory();
        $category->name = $data['name'];
        if(empty($data['title'])){
            $category->title = ucfirst($data['name']);
        }else{
            $category->title = $data['title'];
        }
        $category->description = $data['desc'];
        $category->type = 'General';
        $category->admin_approval = 1;
        $category->created_by_id = Auth::User()->id;
        $category->updated_by_id = Auth::User()->id;
        $category->enabled = 1;
        $category->save();

        $log = new DpiaLogs();
        $log->dpia_id = $category->id;
        $log->type = 'category';
        $log->action = 'add category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($category);
        $log->save();

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_category");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {        
        $dpiaCategory = DpiaCategory::find($id);
        return view('admin.dpia.category.edit', compact('dpiaCategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        $category = DpiaCategory::find($id);
        $category->name = $data['name'];
        if(empty($data['title'])){
            $category->title = ucfirst($data['name']);
        }else{
            $category->title = $data['title'];
        }
        $category->description = $data['desc'];
        //$category->type = 'General';
        $category->admin_approval = 1;
        //$category->created_by_id = Auth::User()->id;
        $category->updated_by_id = Auth::User()->id;
        $category->enabled = 1;
        $category->save();

        $log = new DpiaLogs();
        $log->dpia_id = $category->id;
        $log->type = 'category';
        $log->action = 'update category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($category);
        $log->save();

        //return redirect()->back()->with('sccuess', 'Category Description has been updated');
        return redirect("/admin/dpia_category");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = DpiaCategory::find($id);

        $subcategories = DpiaSubCategory::where(['dpia_category_id'=>$id])->get();

        if(count($subcategories) > 0){
            foreach ($subcategories as $subcategory){

                $log = new DpiaLogs();
                $log->dpia_id = $subcategory->id;
                $log->type = 'subcategory';
                $log->action = 'delete subcategory';
                $log->user_id = Auth::User()->id;
                $log->json = json_encode($subcategory);
                $log->save();

                DpiaSubCategory::destroy($subcategory->id);
            }
        }

        $log = new DpiaLogs();
        $log->dpia_id = $category->id;
        $log->type = 'category';
        $log->action = 'delete category';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($category);
        $log->save();

        DpiaCategory::destroy($id);

        return redirect("/admin/dpia_category");
    }

    public function disable($id)
    {
        //dd('destroy');
        $category = DpiaCategory::find($id);
        if(isset($category->id)) {

            $subcategories = DpiaSubCategory::where(['dpia_category_id'=>$category->id])->get();
            if(count($subcategories) > 0){
                foreach ($subcategories as $subcategory){
                    $subcategory->enabled = 0;
                    $subcategory->updated_by_id = Auth::User()->id;
                    $subcategory->save();

                    $log = new DpiaLogs();
                    $log->dpia_id = $subcategory->id;
                    $log->type = 'subcategory';
                    $log->action = 'disable subcategory';
                    $log->user_id = Auth::User()->id;
                    $log->json = json_encode($subcategory);
                    $log->save();
                }
            }

            $category->enabled = 0;
            $category->updated_by_id = Auth::User()->id;
            $category->save();

            $log = new DpiaLogs();
            $log->dpia_id = $category->id;
            $log->type = 'category';
            $log->action = 'disable category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($category);
            $log->save();
        }

        return redirect("/admin/dpia_category");
    }

    public function enable($id)
    {
        //dd('destroy');
        $category = DpiaCategory::find($id);
        if(isset($category->id)) {
            $category->enabled = 1;
            $category->updated_by_id = Auth::User()->id;
            $category->save();

            $log = new DpiaLogs();
            $log->dpia_id = $category->id;
            $log->type = 'category';
            $log->action = 'disable category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($category);
            $log->save();
        }

        return redirect("/admin/dpia_category");
    }

    public function disapprove($id)
    {
        $category = DpiaCategory::find($id);
        if(isset($category->id)) {
            $subcategories = DpiaSubCategory::where(['dpia_category_id'=>$category->id])->get();
            //dd(count($subcategories));
            if(count($subcategories) > 0){
                foreach ($subcategories as $subcategory){
                    $subcategory->admin_approval = 0;
                    $subcategory->updated_by_id = Auth::User()->id;
                    $subcategory->save();
                    $log = new DpiaLogs();
                    $log->dpia_id = $subcategory->id;
                    $log->type = 'subcategory';
                    $log->action = 'disapprove subcategory';
                    $log->user_id = Auth::User()->id;
                    $log->json = json_encode($subcategory);
                    $log->save();
                }
            }
            $category->admin_approval = 0;
            $category->updated_by_id = Auth::User()->id;
            $category->save();

            $log = new DpiaLogs();
            $log->dpia_id = $category->id;
            $log->type = 'category';
            $log->action = 'disapprove category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($category);
            $log->save();
        }
        return redirect("/admin/dpia_category");
    }

    public function approve($id)
    {
        //dd('destroy');
        $category = DpiaCategory::find($id);
        if(isset($category->id)) {
            $category->admin_approval = 1;
            $category->updated_by_id = Auth::User()->id;
            $category->save();

            $log = new DpiaLogs();
            $log->dpia_id = $category->id;
            $log->type = 'category';
            $log->action = 'approve category';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($category);
            $log->save();
        }

        return redirect("/admin/dpia_category");
    }
}
