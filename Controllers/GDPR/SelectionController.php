<?php

namespace App\Http\Controllers\GDPR;;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Type;
use App\Select;
use App\Radio;
use App\ListBox;
use Session;
class SelectionController extends Controller
{
    public function index()
    {

    	//$types = Type::all();

       // dd(Session::all());
    	//return view('frontend.pages.context',compact('types'));
    	
    }
    public function page1()
    {
        return view('gdpr.frontend.pages.page1');
    }
    public function page2()
    {
        return view('gdpr.frontend.pages.page2');
    }
    public function page3()
    {
        return view('gdpr.frontend.pages.page3');
    }
    public function page4()
    {
        return view('gdpr.frontend.pages.page4');
    }

   /* public function save_field(Request $request)
    {

    	$type = new Type();
    	$type->title = $request['title'];
    	$type->field_type = $request['field_type'];

    	$type->save();

    	if($request['field_type'] == 'select')
    	{
    		foreach($request['select'] as $select)
    		{
    			$new_select = new Select();
    			$new_select->type_id = $type->id;
    			$new_select->title = $select;
    			$new_select->save();
    		}
    	}
    	elseif($request['field_type'] == 'radio')
    	{
    		foreach($request['radio'] as $radio)
    		{
    			$new_radio = new Radio();
    			$new_radio->type_id = $type->id;
    			$new_radio->title = $radio;
    			$new_radio->save();
    		}
    	}
    	elseif($request['field_type'] == 'check')
    	{
    		foreach($request['check'] as $check)
    		{
    			$new_check = new ListBox();
    			$new_check->type_id = $type->id;
    			$new_check->title = $check;
    			$new_check->save();
    		}
    	}

    	return redirect()->route('context');
    }

    public function submit_fields(Request $request)
    {
        $types = Type::all();

        foreach($types as $type)
        {
           if($request[$type->id] != '')
            
            Session::put($type->type_unique_name,$request[$type->id]);
                
        }
        //dd(Session::all());
        return redirect()->back();
    }*/
}
