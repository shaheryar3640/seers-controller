<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorPolicy;
use App\Models\PolicyGeneratorCategory;
use Illuminate\Support\Facades\Input;

class PolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $category_id;

    public function __construct()
    {
        //$this->cate
        //$this->middleware('admin');
    }

    public function index($id)
    {        
        $category = PolicyGeneratorCategory::find($id);//->first();
        $cat_name = $category['name'];
        
        $policies = PolicyGeneratorPolicy::where('policy_generator_category_id', $id)->orderBy('sort_order', 'desc')->get();
        // dd($policies);
        return view('admin.policygenerator.policy.show', compact('policies', 'cat_name', 'id'));
    }

    // public function showPolicies($id)
    // {
    //     return "This is category Id = " . $id;
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cid)
    {
        //dd($this->category_id);
        // $id = $this->category_id;
        // dd($id);
        // $categories = PL_Category::where('enabled',1)->get();

        return view('admin.policygenerator.policy.create',compact('cid'));
        //return "in create";
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $cid)
    {
        // dd($request->all());
        $data = $request->all();
        //dd($data);
        $policy = PolicyGeneratorPolicy::create($request->all());
        //dd($policy);
        if($policy != null)
            return back()->with('success', 'Policy created successfully');//redirect(route('admin.policy.design',['catId' => $catId, 'polId' => $policy->id]));
        else
        return back()->with('error', 'Something went wrong');
        // /return redirect(route('admin.policy.design', ['id' => $id, ''])).;
    }


    public function design()
    {
        //dd($pl_id);
        //dd(Input::get('id'));
        $policy = PolicyGeneratorPolicy::where('id', $polId)->first();
        //dd($policy);
        $policy_data = ($policy->policy_data);
        //dd($policy_data);
        return view('admin.policygenerator.policy.toolkitEditor',['policy' => $policy, 'catId' => $catId, 'polId' => $polId, 'policy_name' => $policy->name,'policy_generator_data'=>$policy_data]);
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
    public function edit($cid, $pid)
    {
        $policy = PolicyGeneratorPolicy::where(['id' => $pid])->first();
        // dd($policy);
        // $categories = PL_Category::where('enabled',1)->get();
        return view('admin.policygenerator.policy.edit', compact('policy', 'cid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cid, $pid)
    {
        // dd("cid ". $cid . " : pid " . $pid);
        //dd($id);
        $policy = PolicyGeneratorPolicy::where(['id' => $pid])->first();
        $policy->fill($request->all());

        $policy->icon = str_slug($policy->name) . '.svg';
        $policy->save();


        return back()->with('success', 'Policy has been updated');
        //return "in update with id = ". $id ." and pl_id = " .$pl_id;
    }

    public function updateDesign(Request $request, $catId, $polId)
    {
        $policy = PolicyGeneratorPolicy::where(['id' => $polId])->first();
        $policy->questions = $request->get('questions');
        $policy->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($cid, $pid)
    {
        $policy = PolicyGeneratorPolicy::where(['id' => $pid])->first();
        //$policy_generator->delete();
        if($policy != null){
            PolicyGeneratorPolicy::destroy($policy->id);
        }
        return back()->with('success', 'Policy deleted successfully');
    }
}
