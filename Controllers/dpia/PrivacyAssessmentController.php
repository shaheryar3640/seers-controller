<?php

namespace App\Http\Controllers\dpia;

use App\Models\Dpia;
use App\Models\DpiaCategory;
use App\Models\DpiaFinalRemarks;
use App\Models\DpiaQuestion;
use App\Models\DpiaStakeHolder;
use App\Models\DpiaSubCategory;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Image;
use PDF;

class PrivacyAssessmentController extends Controller
{

    private $product = null;
    private $features = null;
    private $user = null;

    public function __construct()
    {
//        $this->middleware('business');
        $this->product = 'dpia';
    }

    public function index() {
        // dd('here');
        $this->user = DpiaStakeHolder::where(['user_id' => Auth::User()->id, 'enabled' => 1])->select('user_type', 'created_by_id')->first();

        if ($this->user && $this->user->user_type !== 'owner') {
            $owner = User::find($this->user->created_by_id);
            $product = $owner->currentProduct($this->product);
            if($product && $product->plan->expired_on > date('Y-m-d h:i:s')) {
                return view('dpia.index');
            } else {
                // return redirect()->route('subscription-expired');
                return redirect()->route('price-plan');
            }
        } else {
             if (!hasProduct($this->product)) {
                 session()->put('upgrade_plan', true);
                 // return redirect()->route('subscription-expired');
                 return redirect()->route('price-plan');
             }
        }
        return view('dpia.index');
    }

    public function getReport () {
        $this->user = DpiaStakeHolder::where(['user_id' => Auth::User()->id, 'enabled' => 1])->select('user_type', 'created_by_id')->first();

        if ($this->user->user_type !== 'owner') {
            $owner = User::find($this->user->created_by_id);
            $product = $owner->currentProduct($this->product);
            if($product && $product->plan->expired_on > date('Y-m-d h:i:s')) {
                return view('dpia.report');
            } else {
                return redirect()->route('business.dashboard');
            }
        } else {
            if (!hasProduct($this->product)) {
                session()->put('upgrade_plan', true);
                return redirect()->route('business.price-plan');
            }
        }
        return view('dpia.report');
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function currentUser() {
        $message = null;
        $status_code = null;
        $currentUser = null;
        if(request()->ajax()) {
            $currentUser = DpiaStakeHolder::with('dpia_user')->where(['user_id' => Auth::User()->id])->select('user_type', 'user_id', 'created_by_id')->first();
            if ($currentUser->user_type === 'owner') {
                $product = Auth::User()->currentProduct($this->product);
            } else {
                $product = User::find($currentUser->created_by_id)->currentProduct($this->product);
            }
            if ($product) {
                $features = $product->plan->features;
                if ($features->count() > 0) {
                    foreach ($features as $feature){
                        $this->features[$feature->name] = (int) $feature->value;
                    }
                }
            }
            $message = 'Found';
            $status_code = 200;
        } else {
            $message = 'Bad Request';
            $status_code = 400;
            return response(['message' => $message], $status_code);
        }

        return response([
            'message' => $message,
            'features' => $this->features,
            'user' => $currentUser,
            'asset_path' => asset('/')
        ], $status_code);
    }

    public function getDpiaReport ($slug) {
        $dpia = Dpia::where(['slug' => $slug])->enabled()->whereIn('status', ['validator-complete', 'approved'])->first();

        if (!isset($dpia->id)) {
            return view('errors.500');
        }

        $currentUser = DpiaStakeHolder::with('dpia_user')->where(['user_id' => Auth::User()->id])->select('user_type', 'user_id', 'created_by_id')->first();

        if ($currentUser->user_type === 'owner') {
            $hasPermission = auth()->user()->currentProduct($this->product)->plan->features()->where('name', 'impact_assessment_report')->first();
        } else {
            $hasPermission = User::find($currentUser->created_by_id)->currentProduct($this->product)->plan->features()->where('name', 'impact_assessment_report')->first();
        }

//        $hasPermission = auth()->user()->products()->where('name', 'dpia')->first()->plan->features()->where('name', 'impact_assessment_report')->first();
        $hasPermission = $hasPermission->value == 1;

        if (!$hasPermission) {
            return view('errors.500');
        }

        $isUserAllowed = optional(Auth::User(), function ($user) use ($dpia) {
            $stake_holder = DpiaStakeHolder::where(['user_id' => $user->id])->enabled()->first();
            return ($dpia->editor_id === $user->id ||
                    $dpia->reviewer_id === $user->id ||
                    $dpia->validator_id === $user->id ||
                    $dpia->dpia_dpo->stakeholder_id === $stake_holder->id ||
                    ($dpia->dpia_concern_person && $dpia->dpia_concern_person->stakeholder_id === $stake_holder->id) ||
                    $dpia->created_by_id === $user->id
            );
        });

        if (!$isUserAllowed) {
            return view('errors.500');
        }

        return view('dpia.report', compact('dpia'));
    }

    public function getReportData ($dpia_id, $category_name) {
        $category_id = DpiaCategory::where(['title' => $category_name, 'enabled' => 1])->pluck('id');
        $sub_categories = DpiaSubCategory::where(['dpia_category_id' => $category_id, 'enabled' => 1])->get();

        if ($sub_categories && $sub_categories->count() > 0) {
            foreach ($sub_categories as $sub_category) {
                if ($sub_category->has_evaluation_comment === 1) {
                    $sub_category->final_remark = DpiaFinalRemarks::where(['dpia_id' => $dpia_id, 'sub_category_id' => $sub_category->id])->first();
                }
                $sub_category->questions = DpiaQuestion::where(['dpia_id' => $dpia_id, 'dpia_sub_category_id' => $sub_category->id])->with('finalRemark')->get();
            }
            return response(['sub_categories' => $sub_categories], 200);
        }
    }

    public function getActionPlanData ($dpia_id) {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request']);
        }
        $dpia_management_obj = new DpiaManagementController();
        return response(['record' => $dpia_management_obj->getActionPlanResults($dpia_id)], 200);
    }

    public function getRiskMappingData ($dpia_id) {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request']);
        }
        $dpia_management_obj = new DpiaManagementController();
        return response(['record' => $dpia_management_obj->getRiskMappingResults($dpia_id)], 200);
    }

    public function getRiskOverviewResults ($dpia_id) {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request']);
        }
        $dpia_management_obj = new DpiaManagementController();
        return response(['record' => $dpia_management_obj->getRiskOverviewResults($dpia_id)], 200);
    }

    public function downloadPDFReport ($dpia_id) {

        $dpia = Dpia::find($dpia_id);
        $categories = DpiaCategory::where(['enabled' => 1])->where('name', '!=', 'Validation')->with('sub_categories')->select('id', 'name')->get();

        if($categories->count() > 0) {
            foreach ($categories as $category) {
                $sub_categories = $category->sub_categories()->where('name', '!=', 'Risks Overview')->get();

                if ($sub_categories->count() > 0) {
                    foreach ($sub_categories as $sub_category) {
                        if ($sub_category->has_evaluation_comment === 1) {
                            $sub_category->final_remark = DpiaFinalRemarks::where(['dpia_id' => $dpia_id, 'sub_category_id' => $sub_category->id])->first();
                        }
                        $sub_category->questions = DpiaQuestion::where(['dpia_id' => $dpia_id, 'dpia_sub_category_id' => $sub_category->id])->with('finalRemark')->get();
                    }
                    $category->sub_categories = $sub_categories;
                }
            }
        }

        $pdf = PDF::loadView('dpia.report-pdf', ['data' => $categories, 'dpia' => $dpia]);
        // $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->download( $dpia->name .' Report.pdf');
    }

    public function storeScreenShots (Request $request) {
        if (!$request->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $dpia_id = $request->get('id');
        $dpia = Dpia::find($dpia_id);

        if ($dpia->action_plan_image !== null && $dpia->risk_mapping_image !== null && $dpia->risk_overview_image !== null) {
            return response(['message' => 'Images Stored Successfully'], 200);
        }

        $image_path = base_path('images/dpia/reports');
        if (!is_dir($image_path)) {
            mkdir($image_path, 0777);
        }

        $action_name = str_slug($dpia->name) . '-' . $dpia->id . '-action-plan' . '.png';
        $mapping_name = str_slug($dpia->name) . '-' . $dpia->id . '-risk-mapping' . '.png';
        $overview_name = str_slug($dpia->name) . '-' . $dpia->id . '-risk-overview' . '.png';

        $action_plan_image = $request->get('action_plan');
        $risk_mapping_image = $request->get('risk_mapping');
        $risk_overview_image = $request->get('risk_overview');

        $replace = substr($action_plan_image, 0, strpos($action_plan_image, ',')+1);
        $image = str_replace($replace, '', $action_plan_image);
        $image = str_replace(' ', '+', $image);
        \File::put($image_path. '/' . $action_name, base64_decode($image));

        unset($image);
        unset($replace);
        $replace = substr($risk_mapping_image, 0, strpos($risk_mapping_image, ',')+1);
        $image = str_replace($replace, '', $risk_mapping_image);
        $image = str_replace(' ', '+', $image);
        \File::put($image_path. '/' . $mapping_name, base64_decode($image));

        unset($image);
        unset($replace);
        $replace = substr($risk_overview_image, 0, strpos($risk_overview_image, ',')+1);
        $image = str_replace($replace, '', $risk_overview_image);
        $image = str_replace(' ', '+', $image);
        \File::put($image_path. '/' . $overview_name, base64_decode($image));



        $dpia->action_plan_image = $action_name;
        $dpia->risk_mapping_image = $mapping_name;
        $dpia->risk_overview_image = $overview_name;

        $dpia->save();

        return response(['message' => 'Images Stored Successfully'], 200);
    }
}
