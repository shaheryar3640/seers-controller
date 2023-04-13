<?php

namespace App\Http\Controllers;

use App\Models\SubService;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WebUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Url;
use Illuminate\Routing\Router;
use App;
class IndexController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
    //     $sub_services = SubService::all();
    //     $testimonials = Testimonial::all();
    //     $advisors = User::with(['SellerServices'])->onlyAdvisor()->has('SellerServices')->inRandomOrder()->limit(20)->get();

    //     //return response()->json($advisors);
    //     return view('index')->with(compact('sub_services','testimonials', 'advisors'));

    // }
    public function index()
    {
    // dd(\Session::get('internal'));
    
    if(App::environment('production')){
        if(\Session::get('internal')==null || \Session::get('internal')!=true){
            $ip = $_SERVER['REMOTE_ADDR'];
            rescue(function() use($ip){
                $dataArray = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));
                 $is_brazil = false;
                if($dataArray->geoplugin_countryName === 'Brazil'){
                    $is_brazil = true;
                    return redirect()->route('home.lang',['br']);
                }
                if($dataArray->geoplugin_countryName === 'France'){
                    $is_brazil = true;
                    return redirect()->route('home.lang',['fr']);
                }
                if($dataArray->geoplugin_countryName === 'Germany'){
                    $is_brazil = true;
                    return redirect()->route('home.lang',['de']);
                }
                if($dataArray->geoplugin_countryName === 'Spain'){
                    $is_brazil = true;
                    return redirect()->route('home.lang',['es']);
                }
            },function(){

            },true);
        }
    }
    // $router=new Router;
    // $routes=$router->getRoutes();
    // dd(app('router')->getRoutes());
    // dd(Route::getRoutes()->getRoutes());
    // $page = request()->path();
    // if($page == 'index'){
    //     $page = '/';
    // }
    // $web_url = WebUrl::where(['url'=> $page])->pluck('id');
    // $sub_services = SubService::all();
    // $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
    // $advisors = User::with(['SellerServices'])->onlyAdvisor()->has('SellerServices')->inRandomOrder()->limit(20)->get();
       $sub_services = '';
       $advisors = '';

    $arrays = array(
        array(
            'id'=>1,
            'name' => 'Andrius Petkevicius',
            'description' => '“I manage over 20 websites which is not usually as daunting as it sounds, but with the advent of GDPR I have been especially concerned about obeying privacy laws. Your Cookie banner solution has given me peace of mind',
            'image'=>'Andrius Petkevicius.png',
            'designation'=>'Dev Slate - Lithuania',
            'web_urls_id'=>'1'
        ),
        array(
            'id'=>2,
            'name' => 'Rich Rothschild',
            'description' => 'The only thing better than the product is the customer service!',
            'image'=>'Rich Rothschild.png',
            'designation'=>'BusinessMobiles.com.UK',
            'web_urls_id'=>'1'
        ),
        array(
            'id'=>3,
            'name' => 'Mark Trowbridge',
            'description' => 'We have been using Seers since 2018 and I have to say that their privacy management solutions have transformed the way we view data protection',
            'image'=>'Mark Trowbridge.png',
            'designation'=>'ConXhub. Spain',
            'web_urls_id'=>'1'
        ),
        array(
            'id'=>4,
            'name' => 'Jeff Spires',
            'description' => 'I give them 10/10 for data privacy expertise. Thank you. Highly recommended',
            'image'=>'Jeff SpiresPOW.png',
            'designation'=>'New Media. UK',
            'web_urls_id'=>'1'
        ),
        array(
            'id'=>5,
            'name' => 'Bill Anderson',
            'description' => 'Using Seers has made a significant difference to our business',
            'image'=>'Bill Anderson.png',
            'designation'=>'',
            'web_urls_id'=>'1'
        ),
        array(
            'id'=>6,
            'name' => 'Michael Boevink',
            'description' => 'An outstanding approach to privacy policies with the
                privacy management software that encompasses all of our needs.',
            'image'=>'Michael Boevink.png',
            'designation'=>'Boevink Group',
            'web_urls_id'=>'1'
        ),
    );
    $testimonials = [];
//    foreach($arrays as $array){
//        array_push($testimonials,new Testimonial($array));
//    }


    // return 'hello';
    $newscript = true;
    //return response()->json($advisors);
        
    if (\Session::get('seten') === true) {
        \Session::put('seten', false);
        app()->setLocale('en');
    }

    $lang = app()->getLocale();
    if($lang === 'en')
    $lang = 'en-uk';
    return view('index')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));

}
   public function indexEn()
{
    \Session::put('internal', true);
    \Session::put('seten', true);
    // \Session::forget('internal'); 
    return redirect()->route('home');
}

    public function indexLang($langReq)
    {
        // $router=new Router;
        // $routes=$router->getRoutes();
        // dd(app('router')->getRoutes());
        // dd(Route::getRoutes()->getRoutes());
        // $page = request()->path();
        // if($page == 'index'){
        //     $page = '/';
        // }
        // $web_url = WebUrl::where(['url'=> $page])->pluck('id');
        $sub_services = SubService::all();
        // $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $advisors = User::with(['SellerServices'])->onlyAdvisor()->has('SellerServices')->inRandomOrder()->limit(20)->get();


        $arrays = array(
            array(
                'id'=>1,
                'name' => 'Andrius Petkevicius',
                'description' => '“I manage over 20 websites which is not usually as daunting as it sounds, but with the advent of GDPR I have been especially concerned about obeying privacy laws. Your Cookie banner solution has given me peace of mind',
                'image'=>'Andrius Petkevicius.png',
                'designation'=>'Dev Slate - Lithuania',
                'web_urls_id'=>'1'
            ),
            array(
                'id'=>2,
                'name' => 'Rich Rothschild',
                'description' => 'The only thing better than the product is the customer service!',
                'image'=>'Rich Rothschild.png',
                'designation'=>'BusinessMobiles.com.UK',
                'web_urls_id'=>'1'
            ),
            array(
                'id'=>3,
                'name' => 'Mark Trowbridge',
                'description' => 'We have been using Seers since 2018 and I have to say that their privacy management solutions have transformed the way we view data protection',
                'image'=>'Mark Trowbridge.png',
                'designation'=>'ConXhub. Spain',
                'web_urls_id'=>'1'
            ),
            array(
                'id'=>4,
                'name' => 'Jeff Spires',
                'description' => 'I give them 10/10 for data privacy expertise. Thank you. Highly recommended',
                'image'=>'Jeff SpiresPOW.png',
                'designation'=>'New Media. UK',
                'web_urls_id'=>'1'
            ),
            array(
                'id'=>5,
                'name' => 'Bill Anderson',
                'description' => 'Using Seers has made a significant difference to our business',
                'image'=>'Bill Anderson.png',
                'designation'=>'',
                'web_urls_id'=>'1'
            ),
            array(
                'id'=>6,
                'name' => 'Michael Boevink',
                'description' => 'An outstanding approach to privacy policies with the
                privacy management software that encompasses all of our needs.',
                'image'=>'Michael Boevink.png',
                'designation'=>'Boevink Group',
                'web_urls_id'=>'1'
            ),
        );
        $testimonials = [];
        foreach($arrays as $array){
            array_push($testimonials,new Testimonial($array));
        }


        // return 'hello';
        $newscript = true;
        //return response()->json($advisors);
        $lang = $langReq;
        return view('index')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        // switch($lang){
        //     case 'br':
        //         return view('index-br')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        //     break;
        //     case 'de':
        //         return view('index-de')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        //     break;
        //     case 'fr':
        //         return view('index-fr')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        //     break;
        //     case 'en-us':
        //         return view('index')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        //     break;
        //     case 'en-uk':
        //         return view('index')->with(compact('sub_services','testimonials', 'advisors','newscript','lang'));
        //     break;
        //     default:
        //      return abort(404);
        // }

    }
    
    

    public function sendCookieUrl($domain){
        session()->put('domain',$domain);
        $do = session()->get('domain');
        return response()->json(['url' => '/cookie-scan-page','domain'=>$do], 200);
    }

    public function cookieScanPage(){
        $domain = session()->get('domain');
        return view('cookie-scan-page')->with(compact('domain'));
    }
    
}
