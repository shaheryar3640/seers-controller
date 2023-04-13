<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DpiaCategory;
use App\Models\DpiaSubCategory;
use App\Models\DpiaLogs;
// use Auth;
use Illuminate\Support\Facades\Auth;

class DpiaSubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dpiaSubCategories = DpiaSubCategory::all();
        return view('admin.dpia.subcategory.show', compact('dpiaSubCategories'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        return view('admin.dpia.subcategory.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(DpiaSubCategory::$rules,DpiaSubCategory::$messages);
        $data = $request->all();

        //dd($data['name']);

        $category = DpiaCategory::find($data['category_id']);

        $subcategory = new DpiaSubCategory();
        $subcategory->name = $data['name'];
        if(empty($data['title'])){
            $subcategory->title = ucfirst($data['name']);
        }else{
            $subcategory->title = $data['title'];
        }
        $subcategory->dpia_category_id = $data['category_id'];
        $subcategory->description = $data['desc'];
        $subcategory->type = 'General';
        $subcategory->admin_approval = $category->admin_approval;

        if(isset($data['has_evaluation_comment']) && $data['has_evaluation_comment'] == true){
            $subcategory->has_evaluation_comment = 1;
        }else{
            $subcategory->has_evaluation_comment = 0;
        }

        $subcategory->created_by_id = Auth::User()->id;
        $subcategory->updated_by_id = Auth::User()->id;
        $subcategory->enabled = $category->enabled;
        $subcategory->save();

        $log = new DpiaLogs();
        $log->dpia_id = $subcategory->id;
        $log->type = 'subcategory';
        $log->action = 'add subcategory';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($subcategory);
        $log->save();

        //return redirect()->back()->with('sccuess', 'DPIA Category added successfully');
        return redirect("/admin/dpia_sub_category");
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
        $categories = DpiaCategory::where(['admin_approval'=>1, 'enabled'=>1])->get();
        $dpiaSubCategory = DpiaSubCategory::find($id);
        return view('admin.dpia.subcategory.edit', compact(['dpiaSubCategory', 'categories']));
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

        $subcategory = DpiaSubCategory::find($id);

        $category = DpiaCategory::find($data['category_id']);

        $subcategory->name = $data['name'];
        if(empty($data['title'])){
            $subcategory->title = ucfirst($data['name']);
        }else{
            $subcategory->title = $data['title'];
        }
        $subcategory->dpia_category_id = $data['category_id'];
        $subcategory->description = $data['desc'];
        //$subcategory->type = 'General';
        $subcategory->admin_approval = $category->admin_approval;
        //$subcategory->created_by_id = Auth::User()->id;
        if(isset($data['has_evaluation_comment']) && $data['has_evaluation_comment'] == true){
            $subcategory->has_evaluation_comment = 1;
        }else{
            $subcategory->has_evaluation_comment = 0;
        }
        $subcategory->updated_by_id = Auth::User()->id;
        $subcategory->enabled = $category->enabled;
        $subcategory->save();

        $log = new DpiaLogs();
        $log->dpia_id = $subcategory->id;
        $log->type = 'subcategory';
        $log->action = 'update subcategory';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($subcategory);
        $log->save();

        //return redirect()->back()->with('sccuess', 'Category Description has been updated');
        return redirect("/admin/dpia_sub_category");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //dd('destroy');

        $subcategory = DpiaSubCategory::find($id);

        /*$subcategories = DpiaSubCategory::where(['dpia_category_id'=>$id])->get();

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
        }*/

        $log = new DpiaLogs();
        $log->dpia_id = $subcategory->id;
        $log->type = 'subcategory';
        $log->action = 'delete subcategory';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($subcategory);
        $log->save();

        DpiaSubCategory::destroy($id);

        return redirect("/admin/dpia_sub_category");
    }

    public function disable($id)
    {
        //dd('destroy');
        $subcategory = DpiaSubCategory::find($id);
        if(isset($subcategory->id)) {

            /*$subcategories = DpiaSubCategory::where(['dpia_category_id'=>$category->id])->get();
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
            }*/

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

        return redirect("/admin/dpia_sub_category");
    }

    public function enable($id)
    {
        //dd('destroy');
        $subcategory = DpiaSubCategory::find($id);

        $category = DpiaCategory::find($subcategory->dpia_category_id);

        if($category->admin_approval == 0){
            dd('Its main category is blocked by admin, so you can not enabled subcategory of blocked main category.');
            return redirect("/admin/dpia_sub_category");
        }

        if(isset($subcategory->id)) {
            $subcategory->enabled = 1;
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

        return redirect("/admin/dpia_sub_category");
    }

    public function disapprove($id)
    {
        //dd('destroy');
        $subcategory = DpiaSubCategory::find($id);
        if(isset($subcategory->id)) {

            /*$subcategories = DpiaSubCategory::where(['dpia_category_id'=>$category->id])->get();
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
            }*/

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

        return redirect("/admin/dpia_sub_category");
    }

    public function approve($id)
    {
        //dd('destroy');

        $subcategory = DpiaSubCategory::find($id);

        $category = DpiaCategory::find($subcategory->dpia_category_id);

        if($category->admin_approval == 0){
            dd('Its main category is not approved by admin, so you can not approve sub category without approving of main category.');
            return redirect("/admin/dpia_sub_category");
        }

        if(isset($subcategory->id)) {
            $subcategory->admin_approval = 1;
            $subcategory->updated_by_id = Auth::User()->id;
            $subcategory->save();

            $log = new DpiaLogs();
            $log->dpia_id = $subcategory->id;
            $log->type = 'subcategory';
            $log->action = 'approve subcategory';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($subcategory);
            $log->save();
        }

        return redirect("/admin/dpia_sub_category");
    }
}
