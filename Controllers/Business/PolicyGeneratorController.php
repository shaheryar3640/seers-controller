<?php

namespace App\Http\Controllers\Business;

use App\Models\Document;
use App\Mail\PolicyDocumentMail;
use App\Models\PolicyGeneratorPolicy;
use App\Models\PolicyGeneratorSection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PolicyGeneratorCategory;
use App\Models\PolicyGeneratorHistory;
use App\Models\PolicyGeneratorAnswer;
use Illuminate\Support\Facades\Mail;
use PDF;

class PolicyGeneratorController extends Controller
{

    public function __construct()
    {
    }

    public function index()
    {
        if (!hasProduct('assessment', 'policy_pack')) {
            // return redirect()->route('subscription-expired');
            return redirect()->route('price-plan');
        }
        return view('business.policy-generator-assessment');
    }

    public function loadCategories() {
        $emailAddress = auth()->user()->email;
        if ($emailAddress !== 'izaz.ali@seersco.com') {
            // This one is original query
            $categories = PolicyGeneratorCategory::with('policies')->where('enabled', '=', 1)->orderBy('sort_order', 'DESC')->get();
        } else {
            $categories = PolicyGeneratorCategory::with('policies')->orderBy('sort_order', 'DESC')->get();
        }
        return response(['categories' => $categories], 200);
    }

    public function saveAnswers(Request $request)
    {
        $data = $request->all();
        $policy_answers = PolicyGeneratorAnswer::firstOrCreate([
            'policy_generator_section_id' => $request->get('policy_generator_section_id'),
            'policy_generator_policy_id' => $request->get('policy_generator_policy_id'),
            'user_id' => auth()->id()
        ]);

        $policy_answers->fill($data);
        $policy_answers->save();

        $done = $request->get('done');

        if ($done == true) {
            $policy = PolicyGeneratorPolicy::find($data['policy_generator_policy_id']);
            return response(['policy' => $policy], 200);
        }
    }

    public function saveHistory(Request $request)
    {
        $data = $request->all();
        $history = new PolicyGeneratorHistory;
        $history->user_id = auth()->id();
        $history->policy_generator_policy_id = $data['policy_id'];
        $history->answers = json_encode($data['answers']);
        $history->result = json_encode($data);
        $history->previous_status = 0;
        //        if ($data['compliant'] != null) {
        //        }
        $history->save();
    }

    public function resetSections($policyId)
    {
        $policy_answers = PolicyGeneratorAnswer::where([
            'policy_generator_policy_id' => $policyId,
            'user_id' => auth()->id()
        ])->get();

        if ($policy_answers->count() > 0) {
            foreach ($policy_answers as $policy_answer) {
                $policy_answer->delete();
            }
        }

        $document = Document::where([
            'policy_generator_policy_id' => $policyId,
            'user_id' => auth()->id()
        ])->first();

        if ($document) {
            $document->delete();
        }

        $policy = PolicyGeneratorPolicy::find($policyId);

        return response(['policy' => $policy], 200);
    }

    public function saveUserDocument(Request $request)
    {
        // dd('ok');
        $user_document = Document::firstOrCreate([
            'policy_generator_policy_id' => $request->get('policy_id'),
            'user_id' => auth()->id()
        ]);

        $user_document->policy_status = $request->get('status');
        $user_document->final_document = $request->get('final_document');
        $user_document->save();

        $policy = PolicyGeneratorPolicy::find($request->get('policy_id'));
        return response(['policy' => $policy], 200);
    }

    public function downloadDocument($id)
    {
        $document = Document::where([
            'policy_generator_policy_id' => $id,
            'user_id' => auth()->id()
        ])
            ->first();


        $finaldoc = "";

        if ($document) {
            $finaldoc = $document->final_document;
        }

        $policy = PolicyGeneratorPolicy::find($id);
            $pdf = PDF::loadView('business.policy-generator-document', ['data' => $finaldoc]);
            return $pdf->download($policy->name . ' Document.pdf');

    }

    public function sendEmail(Request $request)
    {
        $document = Document::where([
            'policy_generator_policy_id' => $request->get('policy_id'),
            'user_id' => auth()->id()
        ])
            ->first();
        $policy = PolicyGeneratorPolicy::find($request->get('policy_id'));
        $pdf = PDF::loadView('business.policy-generator-document', ['data' => $document->final_document]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');

        // Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new PolicyDocumentMail($document->final_document, $pdf, $policy->name));
        //
        $to = ['email' => $request->get('email'), 'name' => auth()->user()->fname];
        $template = [
            'id' => config('sendgridtemplateid.Policies-Pack-Complete'),
            'data' => [
                'first_name' => auth()->user()->fname,
                'policy_name' => $policy->name
            ]
        ];
        $file = base64_encode($pdf->output());
        $fileDetails = [ 'content' => $file, 'type' => 'application/pdf', 'filename' => $policy->name. '.pdf' ];
        sendEmailViaSendGrid($to, $template,$fileDetails);
    }

    public function deleteDocument($id)
    {
        $document = Document::where(['policy_generator_policy_id' => $id, 'user_id' => auth()->id()])->first();
        if ($document) {
            $document->delete();
        }
        $policy = PolicyGeneratorPolicy::find($id);
        return response(['policy' => $policy], 200);
    }

    public function showCategoryDetails($slug)
    {
        if (!hasProduct('assessment', 'policy_pack')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }
        $category = PolicyGeneratorCategory::where(['slug' => $slug])->first();//->offset(0)->limit(1)->get();
        $category_slug = PolicyGeneratorCategory::where('enabled', 1)->orderBy('created_at', 'asc')->first();
        return view('business.policy_generator.index', compact('category', 'category_slug'));
    }
    public function showCategoryDetailsNew($slug)
    {
        $category = PolicyGeneratorCategory::where(['slug' => $slug])->first();//->offset(0)->limit(1)->get();
        $category_slug = PolicyGeneratorCategory::where('enabled', 1)->orderBy('created_at', 'asc')->first();
        return view('business.policy_generator.index', compact('category', 'category_slug'));
    }

    public function showPolicyDetails($policy_slug, $section_slug)
    {
        if (!hasProduct('assessment', 'policy_pack')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }

        $policy = PolicyGeneratorPolicy::where(['slug' => $policy_slug])->first();
        $section = PolicyGeneratorSection::where(['policy_generator_policy_id' => $policy->id, 'slug' => $section_slug])->first();
        $category_slug = PolicyGeneratorCategory::where('enabled', 1)->first();

        return view('business.policy_generator.policy', compact('policy', 'section', 'category_slug'));
    }
}
