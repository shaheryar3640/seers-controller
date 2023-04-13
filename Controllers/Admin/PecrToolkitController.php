<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PecrToolkit;

class PecrToolkitController extends Controller
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
        $toolkits = PecrToolkit::orderBy('sort_order','desc')
					->get();
        // dd($toolkits);
        return view('admin.pecr_toolkit.show', compact('toolkits'));
    }

    /* Create */
    public function create()
    {
        return view('admin.pecr_toolkit.create');
    }

    /* Design */
    public function design($id)
    {
        $toolkit = PecrToolkit::find($id);
        //dd($policy_generator);
        $toolkit_data = ($toolkit->toolkit_data);
        //dd($policy_generator_data);
        return view('admin.pecr_toolkit.toolkitEditor',['pecr_toolkit' => $toolkit, 'id' => $id, 'toolkit_name' => $toolkit->toolkit_name, 'toolkit_data' => $toolkit_data]);
    }

    /* Update Design */
    public function updateDesign($id, Request $request)
    {
        
        $toolkit = PecrToolkit::find($id);
        $toolkit->questions = $request->get('questions');
        $toolkit->save();
        //DB::update('update toolkits set toolkit_data=? where id = ?', [$toolkit_data,$id]);
        return response()
            ->json($request->all());
    }

    /* Add */
    public function store(Request $request)
    {

        $data = $request->all();

        $toolkit_name = $data['name'];
        $toolkit = PecrToolkit::create($request->all());

        //$policy_generator = DB::insertGetId('insert into toolkits (toolkit_name) values (?)', [$toolkit_name]);
        //$toolkit_id = DB::table('toolkits')->insertGetId(['toolkit_name' => $toolkit_name]);

        return redirect(route('admin.pecr_toolkit.design',['id'=>$toolkit->id]));
    }


    /* Edit */
    public function info($id)
    {
//        dd($id);
        $toolkit = PecrToolkit::find($id);
        //dd($toolkit);
        return response()
            ->json($toolkit);

    }

    public function update($id, Request $request){
        // dd($request->all());
        $toolkit = PecrToolkit::find($id);
        $toolkit->fill($request->all());
        $toolkit->save();
        return back()->with('success', 'Toolkit has been updated');

    }

    /* Delete */
	
	public function destroy($id) {
      //DB::delete('delete from policy_generators where id = ?',[$id]);

        $toolkit = PecrToolkit::find($id);
        //$policy_generator->delete();
        if($toolkit != null){
            PecrToolkit::destroy($toolkit->id);
        }

        return back();
   }
	public function routePecrToolkitEdit($id) {
      return view('admin.pecr_toolkit.edit',['policy_generator'=>PecrToolkit::find($id)]);
   }
}