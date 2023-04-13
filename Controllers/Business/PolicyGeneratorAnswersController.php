<?php

namespace App\Http\Controllers\Business;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\Mail\PolicyDocumentMail;

use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorAnswer;
use App\Models\PolicyGeneratorPolicy;
use App\Models\PolicyGeneratorSection;
use App\Models\PolicyGeneratorHistory;
use App\Models\PolicyDocument;
use App\Models\User;
use Auth;
use PDF;

class PolicyGeneratorAnswersController extends Controller
{
    //
    public function saveAnswers(Request $request)
    {
        $data = $request->all();
        var_dump('here');
        print_r($data);
        // die();
        $policy_answers = PolicyGeneratorAnswer::firstOrCreate([
            'policy_generator_section_id' => $request->get('policy_generator_section_id'),
            'policy_generator_policy_id' => $request->get('policy_generator_policy_id'),
            'user_id' => Auth::User()->id]);

        $policy_answers->fill($request->all());

        $policy_answers->save();
        
        print_r($policy_answers);

        $done = $request->get('done');

        if($done == true){
            return response()->json(['policy_answers' => $policy_answers]);
        }
    }
    
    public function showResult($slug)
    {
        $policy = PolicyGeneratorPolicy::where('slug', $slug)->firstOrFail();
       
        $current_section = PolicyGeneratorSection::with('answer')->where(['policy_generator_policy_id' => $policy->id])->first();
        
        if($current_section->count() <= 0)
            return view('errors.404');
        $sections = PolicyGeneratorSection::with('answer')->where('sort_order','>','0')->where('policy_generator_policy_id', $policy->id)->orderBy('sort_order', 'asc')->get();
        $category_slug = \App\Models\PolicyGeneratorCategory::where('enabled', 1)->first();
        return view('business.policy_generator.report')->with(['sections' => $sections, 'current_section' => $current_section, 'policy' => $policy, 'category_slug' => $category_slug]);
    }
    
    public function showCurrentDocument($slug)
    {
        $policy = PolicyGeneratorPolicy::where(['slug' => $slug, 'enabled' => 1])->first();
        $document = PolicyDocument::where('policy_generator_policy_id', $policy->id)->first();
        $user = User::find(Auth::User()->id);
        $category_slug = \App\Models\PolicyGeneratorCategory::where('enabled', 1)->first();
        return view('business.policy_generator.documents.single-document', compact('policy', 'document', 'user', 'category_slug'));
    }
    
    public function showProgress($id)
    {
        $total_sections = PolicyGeneratorSection::where('policy_generator_policy_id', $id)->count();
        $done_answers = PolicyGeneratorAnswer::where(['user_id' => Auth::User()->id, 'done' => 1, 'policy_generator_policy_id' => $id])->count();
        $progress = 0;
        $progress = ($done_answers / $total_sections) * 100;
        $progress = (int)$progress;
    
        $user = \App\Models\User::find(Auth::User()->id);
        
        $document = \App\Models\Document::firstOrCreate([
            'policy_generator_policy_id' => $id,
            'user_id' => Auth::User()->id]);
        
        $document->policy_status = $progress;
        $document->save();
        
        if($document != null)
        {
            return response()->json(['progress' => $progress,'total_sections' => $total_sections,'done_answers' => $done_answers , 'user' => $user]);
        }
    }
    
    public function saveResult(Request $request)
    {
        $data = json_encode($request->all());
        
        $policy_id = $request->get('policy_generator_policy_id');
        $policy_answers = PolicyGeneratorAnswer::where(['policy_generator_policy_id' => $request->get('policy_generator_policy_id'),'user_id' => Auth::User()->id])->get();
        
        if($request->get('retry') == true){

            $p_history = new PolicyGeneratorHistory();
            $p_history->user_id = Auth::User()->id;
            $p_history->policy_generator_policy_id = $policy_id;
            $p_history->answers = json_encode($request->get('answers'));
            $p_history->result = $data;
            if($request->get('compliant') != null) {
                $p_history->previous_status = $request->get('compliant');
            }
            $p_history->save();

            //dd()
            //$toolkit->delete();
            $document = null;
            
            if($policy_answers != null)
            {
                foreach($policy_answers as $policy_answer)
                {
                    PolicyGeneratorAnswer::destroy($policy_answer->id);
                }                
                $document = \App\Models\Document::where(['policy_generator_policy_id' => $policy_id, 'user_id' => Auth::User()->id])->delete();
            }
            
            if($document != null)
            {
                $policy = PolicyGeneratorPolicy::where(['id' => $request->get('policy_generator_policy_id')])->first();
                $section = PolicyGeneratorSection::where(['policy_generator_policy_id' => $policy->id])->orderBy('sort_order', 'asc')->first();
                return response()->json(['url'=>route('business.policy.readiness',  ['policy_slug' => $policy->slug, 'section_slug' => $section->slug])]);
            }
        }        
    }
    
    public function downloadPdf($id)
    {
        $document = \App\Models\Document::where(['policy_generator_policy_id' => $id, 'user_id' => Auth::User()->id])->first();
        $policy = PolicyGeneratorPolicy::find($id);
        // dd($policy);
        $data = $document->final_document;
        // dd($data);
        $pdf = PDF::loadView('business.policy_generator.result', ['data' => $data]);
        return $pdf->download($policy->name.' Document.pdf');
    }

    public static function emailReport($id, Request $request)
    {
        $policy = \App\Models\Document::where(['policy_generator_policy_id' => $id, 'user_id' => Auth::User()->id])->first();
        $policy_name = PolicyGeneratorPolicy::find($id);
        $policy_name = $policy_name['name'];
        $data = $policy->final_document;
        
        $user = User::find(Auth::User()->id);

        $pdf = PDF::loadView('business.policy_generator.result', ['data' => $data]);
        Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new PolicyDocumentMail($data,$pdf,$user,$policy_name));
          //  $to = ['email' => $request->get('email'), 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['data' => $data, 'policy_name' => $policy_name, 'user' => $user]
//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);
    }
}
