<?php
namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Mail\BookingCustomerAfterPaying;
use App\Mail\PaymentConfirmation;
use App\Models\SellerService;
use Auth;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use App\Models\Booking;
use App\Models\Rating;
use App\Mail\AdvisorBeingBooked;



class BookingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       // $this->middleware('business');

    }
    public function payment_booking_advisor(){
        return view('payment');
    }

    public function processBookDpo(Request $request)
    {

        //$hours = 3;//$request->get('hours');
      //  $message = $request->get('message');
        $price = $request->get('price');

        $paymentMethod = $request->get('paymentMethod');


        $stripe_customer = $this->getStripeCustomer($paymentMethod['card'], $request->get('email'));
       // $stripe_customer = $this->getStripeCustomer($token['id']);
       // $seller_service = SellerService::find($request->get('seller_services_id'));

        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $paymentIntent = PaymentIntent::create([
                "payment_method" => $paymentMethod['id'],
                "amount" => (int)($price * 100),
                "currency" => "gbp",
                "customer" => $stripe_customer->id,
                "payment_method_types" => ["card"],
                'setup_future_usage' => 'off_session',
                "description" => $request->get('payment_reference')
            ]);
            $paymentResponse = $this->generatePaymentResponse($paymentIntent);
        } catch(\Stripe\Error\Card $e) {
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        }catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return response()
                ->json(['e'=>$e,'message' =>'Your credit card has been declined. Please try again or contact us.','page'=>route('business.bookingFailed')],400);
        }


  /*      $booking = Booking::create([
            'charge_id' => $paymentResponse->id,
            'amount_hours' => $hours,
            'price_per_hour' => $seller_service->price,
            'amount_payment' => ($seller_service->price * $hours),
            'seller_id' => $seller_service->user_id,
            'currency' => 'gbp',
            'buyer_id' => '0',
            'subservice_id' => $seller_service->subservice_id
        ]);*/

        $this->notify($request->all());
        return response()
            ->json(['message' => 'Your transection has been completed successfully']);
    }

    public function notify($booking){

        /* AdvisorBeingBooked email to admin */
      //  Mail::send(new AdvisorBeingBooked($booking));
        /* end AdvisorBeingBooked */

        /* BookingCustomerAfterPaying */
       // Mail::send(new BookingCustomerAfterPaying($booking));
        /* end BookingCustomerAfterPaying */

        /* PaymentConfirmation */
         Mail::send(new PaymentConfirmation($booking));
           //  $to = ['email' => $booking['email'], 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0',
//             'data' => [ 'booking' => $booking]

//         ];
//         sendEmailViaSendGrid($to, $template);

        /* end PaymentConfirmation */

    }

    public function generatePaymentResponse($intent) {
        //dd($intent);
        if ($intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk') {
            # Tell the client to handle the action
            return $intent;
        }else if ($intent->status == "requires_payment_method") {
            return $intent;
        } else if ($intent->status == "requires_confirmation") {
            $confirm_payment = PaymentIntent::retrieve($intent->id);
            $confirm_payment->confirm();
            if ($confirm_payment->status == "succeeded") {
                # The payment didn’t need any additional actions and completed!
                # Handle post-payment fulfillment
                return $confirm_payment;
            }else{
                return $intent;
            }
        }else {
            # Invalid status
            return $intent;
        }
    }

    public function getStripeCustomer($intent, $email)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        //Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        /*var_dump('In getStripeCustomer $this->isStripeCustomer()',$this->isStripeCustomer());
        var_dump('In getStripeCustomer Auth::user()->id',Auth::user()->id);*/
        $customer = null;
        $customer = Customer::create(array(
            "description" => $email
        ));
        return $customer;
    }

    public function createStripeCustomer($intent)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        //Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        $customer = Customer::create(array(
            "description" => $intent
        ));

        return $customer;
    }

    /**
     * Check if the Stripe customer exists.
     *
     * @return boolean
     */
    public function isStripeCustomer()
    {
        return Auth::check() && User::where('id', Auth::user()->id)->whereNotNull('stripe_id')->first();
    }

    public function createReviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stars' => 'required',
            'review' => 'required',
        ],[
            'stars.required' => 'Stars is required',
            'review.required' => 'Review is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('business.profile_update_error')],400);
        }
        $user_id = Auth::User()->id;
        //dd($request->get('seller_id'));
        $booking = Booking::where(['id'=> $request->get('ratable_id')])->first();
        $rating = new Rating();
        $rating->review = $request->get('review');
        $rating->stars = $request->get('stars');
        $rating->seller_id = $request->get('seller_id');
        $rating->buyer_id = Auth::User()->id;

        $booking->ratings()->save($rating);

        return response()->json(['message' =>'Success', 'booking' => $booking]);
        //return view('business.result')->with(['data' => $data]);
    }

    public function getBookingsUnique(){
        $bookings = Auth::User()->buyerBookings;

        //$uniqueBookings = $bookings;
        $uniqueBookings = array();

        $unreadMessages = array();

        $allUnReadMessages = 0;

        foreach($bookings as $booking){

            if($booking->bookingRatings == null){
                $uniqueBookings[] = $booking;
                //dd($booking->conversation);
                if($booking->conversation != null) {
                    $unreadConv = $this->getUnreadMessagesOfConversation($booking->conversation);
                    //dd($unreadConv);
                    $unreadMessages[$booking->id] = $unreadConv;
                    $allUnReadMessages = $allUnReadMessages + $unreadConv;
                }
            }
        }


        /*foreach($bookings as $booking){
            $check = 0;
            if($booking->conversation != null){

                foreach($uniqueBookings as $uniqueBooking){
                    if((($uniqueBooking->seller_id == $booking->seller_id) && ($uniqueBooking->buyer_id == $booking->buyer_id)) || (($uniqueBooking->seller_id == $booking->buyer_id) && ($uniqueBooking->buyer_id == $booking->seller_id))){
                        $check = 1;
                    }
                }

                if($check == 0) {
                    $uniqueBookings[] = $booking;
                }
            }
        }*/
        //dd($unreadMessages);
        //dd($allUnReadMessages);

        //dd($uniqueBookings);

        return response()->json(['business_bookings'=>$uniqueBookings, 'unreadMessages'=>$unreadMessages, 'allUnReadMessages'=>$allUnReadMessages , 'avatar_link' => Auth::User()->avatar_link]);
    }

    public function getUnreadMessagesOfConversation($conversation){
        //$conv = $conversation->id;
        $conv_count = 0;
        foreach($conversation->messages as $message){
            if(($message->read == 0) && ($message->user_id != Auth::User()->id)){
                $conv_count ++;
            }
        }
        return $conv_count;
    }

    public static function getUserBookings()
    {
        $bookings = Auth::User()->buyerBookings;

        //$uniqueBookings = $bookings;
        $uniqueBookings = array();

        foreach ($bookings as $booking) {

            if ($booking->bookingRatings == null) {
                $uniqueBookings[] = $booking;
            }
        }

        return $uniqueBookings;
    }
    public static function routegetBookings()
    {
       $booking = Auth::User()->buyerBookings;
        return response()->json(['business_bookings'=>$booking->reverse()->values(), 'avatar_link' => Auth::User()->avatar_link]);
    }
    public static function routegetAdvisorProfile($slug,$subservice_id)
    {
       Session::put('onlySubService',$subservice_id);
        $advisor = User::where('slug','=',$slug)->onlyAdvisor()->with('OnlySellerService')->has('OnlySellerService')->first();
        Session::remove('onlySubService');
        return response()->json(['profile'=>$advisor]);
    }
    public static function routePricePlan()
    {
       $data=[
            'is_login' => Auth::check(),
            'region' => null
        ];
        return view('price-plan-new')->with($data);
    }
    public static function routebookingFailed()
    {
      return view('business.booking_failed');
    }
    public static function routeBookings()
    {
      return view('business.booking');
    }
    public static function routegetAdvisorServices()
    {
      return response()->json(['advisor_subservices'=>Auth::User()->sellerServicesPricing,'avatar_link' => Auth::User()->avatar_link]);
    }
}
