<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\PecrToolkit;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class PolicyGeneratorsController extends Controller
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
    public function index()
    {
        $policy_generators = PecrToolkit::orderBy('sort_order','desc')
					->get();

        return view('admin.pecr_toolkit.show', ['policy_generators' => $policy_generators]);
    }

    /* Create */
    public function create()
    {
        return view('admin.pecr_toolkit.create');
    }

    /* Design */
    public function design($id)
    {
        $policy_generator = PecrToolkit::find($id);
        //dd($policy_generator);
        $policy_generator_data = ($policy_generator->policy_generator_data);
        //dd($policy_generator_data);
        return view('admin.pecr_toolkit.toolkitEditor',['policy_generator' => $policy_generator, 'id' => $id, 'policy_generator_name'=>$policy_generator->policy_generator_name,'policy_generator_data'=>$policy_generator_data]);
    }

    /* Update Design */
    public function updateDesign($id, Request $request)
    {

        //$toolkitData= $request->get('toolkit_data');
        //$toolkit_data = json_decode($toolkitData);
        //$toolkit_data = serialize($request->get('questions'));
        $policy_generator = PecrToolkit::find($id);
        $policy_generator->questions = $request->get('questions');
        $policy_generator->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /* Add */
    public function store(Request $request)
    {

        $data = $request->all();

        $policy_generator_name = $data['name'];
        $policy_generator = PecrToolkit::create($request->all());

        //$policy_generator = DB::insertGetId('insert into toolkits (toolkit_name) values (?)', [$toolkit_name]);
        //$toolkit_id = DB::table('toolkits')->insertGetId(['toolkit_name' => $toolkit_name]);

        return redirect(route('admin.policy_generators.design',['id'=>$policy_generator->id]));
    }


    /* Edit */
    public function info($id)
    {
//        dd($id);
        $policy_generator = PecrToolkit::find($id);
        //dd($toolkit);
        return response()
            ->json($policy_generator);

    }

    public function update($id, Request $request){
        //dd($request->all());
        $policy_generator = PecrToolkit::find($id);
        $policy_generator->fill($request->all());
        $policy_generator->save();
        return back()->with('success', 'Policy Generators has been updated');

    }


    /* Delete */
	
	public function destroy($id) {
      //DB::delete('delete from policy_generators where id = ?',[$id]);

        $policy_generator = PecrToolkit::find($id);
        //$policy_generator->delete();
        if($policy_generator != null){
            PecrToolkit::destroy($policy_generator->id);
        }

        return back();
   }
	
}