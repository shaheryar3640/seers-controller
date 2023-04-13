<?php

namespace App\Http\Controllers\Business;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;

class PolicyDocumentController extends Controller
{
    //
    public function myDocuments($slug)
    {
        $category = \App\PolicyGeneratorCategory::where(['enabled' => 1, 'slug' => $slug])->first();
        
        $categorySlug = \App\PolicyGeneratorCategory::where('enabled', 1)->first();
        
        $user = Auth::user();
        return view('business.policy_generator.documents.index', compact('category', 'categorySlug', 'user'));
    }
    
    public function saveDocument(Request $request)
    {
        $test = $request->all();
        $document = \App\Document::firstOrCreate([
            'policy_generator_policy_id' => $request->get('policy_generator_policy_id'),
            'user_id' => Auth::User()->id,
            ]);

        $document->fill($request->all());
        $document->save();

        if($document != null)
            return response()->json(['document' => $document]);
    }

    public function showAllPolicyDocuments(){
        $allDocuments = \App\PolicyGeneratorAnswers::groupBy('policy_id')->having('user_id', Auth::user()->id)->with('document')->get();
        
        return response()->json(['allDocuments' => $allDocuments]);
    }
}
