<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AutoBlock;
use App\Models\ScriptCategory;
use App\Models\Setting;
use DataTables;

class AutoBlockController extends Controller
{
    public function index(Request $request)
    {
        $crawlers = Setting::orderBy('id','desc')->take(2)->get();
        $autoblocks = AutoBlock::with('script_category')->paginate(10);
        // dd($autoblocks);
        // $banner_names = $autoblocktable->sortBy('domain_name')->pluck('domain_name')->unique();
        return view('admin.auto_block.index', compact('autoblocks','crawlers'));
    }
    public function create(){
        $script_category = ScriptCategory::all();
        // dd($script_category);
        return view('admin.auto_block.create', compact('script_category'));
    }
    public function store(Request $request)
    { 
        $request->validate([
            'tag' => 'required',
            'title' => 'required',
            'src' => 'required',
            'domain_name' => 'required',
            'type' => 'required',
            'purpose' => 'required',
            'is_active' => 'required',
            'script_category_id' => 'required',
        ]);
        // $is_active = ($request->is_active)   
        $autoblock = new AutoBlock;
        $autoblock->tag = $request->tag;
        $autoblock->title = $request->title;
        $autoblock->src = $request->src;
        $autoblock->domain_name = $request->domain_name;
        $autoblock->type = $request->type;
        $autoblock->purpose = $request->purpose;
        $autoblock->is_active = $request->is_active;
        $autoblock->script_category_id = $request->script_category_id;
        $autoblock->save();
        return redirect('/admin/auto_block')->with(['success'=>'Auto Block Add Successfully']);
    }
    public function edit($id){
        $autoblock = AutoBlock::with('script_category')->find($id);
        $script_category = ScriptCategory::all();
        // dd($autoblock->script_category->name);
        if(!empty($autoblock)){
            return view('admin.auto_block.edit',compact('autoblock', 'script_category'));
        }
        else{
            return back();
        }
        
    }
    public function update(Request $request){
        $request->validate([
            'tag' => 'required',
            'title' => 'required',
            'src' => 'required',
            'domain_name' => 'required',
            'type' => 'required',
            'purpose' => 'required',
            'is_active' => 'required',
            'script_category_id' => 'required',
        ]);
        $update_auto_block = AutoBlock::where('id', $request->id)->first();
        $update_auto_block->tag = $request->tag;
        $update_auto_block->title = $request->title;
        $update_auto_block->src = $request->src;
        $update_auto_block->domain_name = $request->domain_name;
        $update_auto_block->type = $request->type;
        $update_auto_block->purpose = $request->purpose;
        $update_auto_block->is_active = $request->is_active;
        $update_auto_block->script_category_id = $request->script_category_id;
        $update_auto_block->save();
        
        return redirect('/admin/auto_block')->with('success', 'Updates Successfully');
    }
    public function delete($id){
        $autoblock = AutoBlock::find($id);
        if(!empty($autoblock)){
            $autoblock->delete();
            return back()->with('success', 'Auto Block has been deleted successfully!');
        }
        else{
            return back();
        }
    }
    public function rebot($id){
        $crawler = Setting::find($id);
        if($crawler){
            $crawler->update([
                'crawler_status' => 0
            ]);
            return back()->with('success', 'Crawler Rebot has been successfully!');
        }
        else{
            return back();
        }
    }
}
