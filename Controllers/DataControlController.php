<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use App\Models\User;
use Auth;
// use App\DataControl;
use View;

class DataControlController extends Controller
{

    public function __construct()
    {

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!hasProduct('assessment', 'data_control')) {
            session()->put('upgrade_plan', true);
            return redirect()->route('business.price-plan');
        }

        if (Auth::User()->user_type == 'Business') {
            return view('datacontrol.datacontrol');
        } else {
            return redirect('/business/price-plan');
        }
    }

    public function dataControl(){
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('salepages.data-control-sales')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }


    public function store(Request $request)
    {
        if($request->get('pdf'))
        {
            $data = $request->all();
            if($data['process_type'] == 'delete'){
                $pdf = PDF::loadView('datacontrol.delete-report', ['data' => $data]);
            }else{
                $pdf = PDF::loadView('datacontrol.report', ['data' => $data]);
            }
           
            return $pdf->download('DataControl.pdf');
        }
    
        // else if($request->get('word'))
        // {
        //      $phpWord = new \PhpOffice\PhpWord\PhpWord();
        //        $section = $phpWord->addSection();

        //        $user = Auth::User();

        //        $company = htmlspecialchars($request->get('company'));
        //        $address = htmlspecialchars($request->get('address'));
        //        $find = '';
        //        $delete = '';
        //        $date = date('d-m-Y');
        //        $name = $request->get('name');

        //        $addressfields = $request->get('addressfields');
        //        $length = count($addressfields);

        //        $questions = $request->get('questions');

        //        $email = $user->email;

        //         if($request->get('process_type') == 'delete'){ 

        //             $permissions = $request->get('permissions');

        //                 $delete .= "<p>". $company ."</p>";

        //                if($permissions != ""){
        //                     $address = explode(',', $address);
        //                     $delete .= "<p>";
        //                     foreach ($address as $line) {
        //                         $delete .= $line . "<br/>";
        //                     }
        //                     $delete .= "</p>";    
        //                }

        //                $delete .= "<p>Date: ". $date ."</p><br/>";

        //                $delete .= "<p style='text-decoration: underline;'>Subject: Request related to the right of erasure (right to be forgotten), as defined in Article 17 of the General Data Protection Regulation (GDPR)</p><br/>";

        //                $delete .= "<p>Dear Data Protection Officer or responsible for Data Protection and Privacy at ". $company .", </p>";

        //                 $delete .= "<p>I hereby contact you in order to exercise my right of access, defined in Article 15 of the General Data Protection Regulation.<br/><br/>As stated in the first paragraph of this article, I would like you to delete my personal data as soon as possible due to the following points:</p><ul style='padding:20px 0px 20px 0px'>";

        //                          foreach($questions as $key => $question){
        //                             $delete .= "<li>". $question . "</li>";
        //                          }
                             

        //                 $delete .= "</ul><br/><p>If necessary, I would also like to be informed as soon as possible of the applicable legal bases to justify that you do not erase my personal data in case the processing of my personal data is necessary:</p><ul><li>'The exercise of the right to freedom of expression and information';</li><li>'To comply with a legal obligation that requires treatment under Union law or the law of the Member State to which the controller is subject, or to perform a public interest or exercise of the public authority of which the controller is entrusted';</li><li>'For reasons of public interest in the field of public health';</li><li>'For archival purposes in the public interest, for scientific or historical research purposes or for statistical purposes';or</li><li>'Recognition, exercise or defense of rights in court'</li></ul><br/><p>Please also note that my application relates to the deletion of 'any link to, copy or replication of such personal data'.<br /><br />In line with Article 19 of the GDPR, I also ask you to communicate my request 'to each recipient to whom the personal data have been communicated ... unless such communication is impossible or requires disproportionate efforts', and that in this case you:</p><ul><li>Inform me of all the recipients concerned;</li><li>you explained to me why it seemed impossible to you or required disproportionate efforts to communicate my request to them</li></ul><br /><p>For the purpose of processing my request, I am providing the following identifiers:</p><ul style='padding:20px 0px 20px 0px'>";

        //                     for($i = 0; $i < $length; $i += 2)
        //                         {
        //                            $delete .= "<li>".$addressfields[$i]['address'] . ' : ' . $addressfields[$i + 1]['value']."</li>";
        //                         }
                             
        //                    $delete .= "</ul><br /><p>Please provide your response to my email address " .$email. "</p><br /><p>In accordance with GDPR Article 12.3, I would like to remind you that my request should be treated 'as soon as possible and in any event within one month of receipt of the request' and that if you needed to extend this 'two-month period, given the complexity and the number of requests', you should keep me informed 'of this extension and the reasons for the postponement within one month from the receipt of this request. 
        //              </p>
        //              <br />

        //                     <h2>Sincerely,<br/>"
        //                     .$name."</h2>";

        //                 \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        //                 \PhpOffice\PhpWord\Shared\Html::addHtml($section, $delete, false, false);
        //                 // $text = $section->addText($request->get('number'),array('name'=>'Arial','size' => 20,'bold' => true));
        //                 // $section->addImage("./images/Krunal.jpg");  
        //         }else{
               
        //             $permissions = $request->get('permissions');

        //                 $find .= "<p>". $company ."</p>";

        //                if($permissions != ""){
        //                     $address = explode(',', $address);
        //                     $find .= "<p>";
        //                     foreach ($address as $line) {
        //                         $find .= $line . "<br/>";
        //                     }
        //                     $find .= "</p>";    
        //                }

        //                $find .= "<p>Date: ". $date ."</p><br/>";

        //                $find .= "<p style='text-decoration: underline;'>Subject: Right of Access as defined in Article 15 GDPR </p><br/>";

        //                $find .= "<p>Dear Data Protection Officer or responsible for Data Protection and Privacy at ". $company .", </p>";

        //                 $find .= "<p>I hereby contact you in order to exercise my right of access, defined in Article 15 of the General Data Protection Regulation.<br/><br/>As stated in the first paragraph of this article, I would like to obtain confirmation from you as to whether you are processing my personal data. <br/><br/>If this is the case, I would be grateful if you would communicate to me all the personal data concerned and the following information: </p><ul style='padding:20px 0px 20px 0px'>";

        //                          foreach($questions as $key => $question){
        //                             $find .= "<li>". $question . "</li>";
        //                          }
                             

        //                 $find .= "</ul><br/><p>This request relates to any processing of my personal data by ". $company .", including any processors processing personal data on behalf of ". $company .".</p><br/>
        //                     <p>For the purpose of processing my request, I am providing the following identiﬁers:
        //                     </p><ul style='padding:20px 0px 20px 0px'>";

        //                     for($i = 0; $i < $length; $i += 2)
        //                         {
        //                            $find .= "<li>".$addressfields[$i]['address'] . ' : ' . $addressfields[$i + 1]['value']."</li>";
        //                         }
                             
        //                    $find .= "</ul><br/><p>Please provide your response to my email address". $email ."
        //                     </p><br/><p>In accordance with GDPR Article 12.3, I would like to remind you that my request should be treated 'as soon as possible and in any event within one month of receipt of the request' and that if you needed to extend this 'two-month period, given the complexity and the number of requests', you should keep me informed 'of this extension and the reasons for the postponement within one month from the receipt of this request. 
        //                      </p><br/>

        //                     <h2>Sincerely,<br/>"
        //                     .$name."</h2>";

        //                 \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        //                 \PhpOffice\PhpWord\Shared\Html::addHtml($section, $find, false, false);
        //                 // $text = $section->addText($request->get('number'),array('name'=>'Arial','size' => 20,'bold' => true));
        //                 // $section->addImage("./images/Krunal.jpg");  
        //             }

        //         $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        //         $objWriter->save('DataControl.docx');
        //         return response()->download('DataControl.docx');
        // }

    }
}
