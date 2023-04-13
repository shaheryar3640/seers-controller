<?php

namespace App\Http\Controllers\Business;

use App\Models\Booking;
//use Wqqas1\LaravelVideoChat\Models\Conversation\Conversation;
use Wqqas1\LaravelVideoChat\Models\Message\Message as Message;
use App\Models\BuyerSubService;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Wqqas1\LaravelVideoChat\Facades\Chat;
use Wqqas1\LaravelVideoChat\Models\File\File;

//use File;
use Image;
use Socialite;

class ChatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('business');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $groups = Chat::getAllGroupConversations();
        $threads = Chat::getAllConversations();
        //dd($threads);
        return view('business.chat.home')->with([
            'threads' => $threads,
            'groups'  => $groups
        ]);
    }



    public function chat($id)
    {

        //$currentConversation = Conversation::where('id', $id)->first();
        //$conversation = Chat::getAllConversationsWithMessages($currentConversation->first_user_id, $currentConversation->second_user_id);
        //$conversation = Chat::getAllConversationsWithMessages(331, 272);
        //$conversation->sortBy('created_at');
        //$messages = collect($conversation['messages']);
        //dd($messages->sortByDesc('created_at'));
        //$conversation['messages'] = '';
        /*foreach($messages->sortByDesc('created_at') as $message) {
            $conversation['messages'][] = collect($message);
        }*/
        //$conversation['messages'] = collect($messages->sortByDesc('created_at')->sortKeys());
        //dd($conversation);
        $conversation = Chat::getConversationMessageById($id);
        //dd($conversation);

        return view('business.chat.chat')->with([
            'conversation' => $conversation
        ]);

        /*return view('business.chat.businessChat')->with([
            'conversation' => $conversation
        ]);*/
    }

    public static function getChat($id, $chatid)
    {
        $conversation = Chat::getConversationMessageById($id);
        //dd($conversation['conversationId']);
        $messages = Message::where('conversation_id', $conversation['conversationId'])->get();
        //dd($messages);
        if($chatid != 0) {
            foreach ($messages as $message) {
                if ($message->read == 0) {
                    $oldMessage = Message::find($message->id);
                    $oldMessage->read = 1;
                    $oldMessage->save();
                }
            }
        }
        return $conversation;
    }

    public function groupChat($id)
    {
        $conversation = Chat::getGroupConversationMessageById($id);

        return view('business.chat.group_chat')->with([
            'conversation' => $conversation
        ]);
    }

    public function InitialiseChatRequest($id){
        //$booking = Booking::find($id);
        $booking = Booking::where('id',$id)->first();
        if($booking == null) {
            return response()->json(['message' =>'Cannot chat'],400);
        }else{
            $conversation = new Conversation();
            $conversation->first_user_id = Auth::User()->id;

            //dd('Info  : ',$booking->Seller->id);
            if (Auth::User()->id != $booking->Seller->id) {
                $conversation->second_user_id = $booking->Seller->id;
            } else {
                $conversation->second_user_id = $booking->Buyer->id;
            }

            $conversation->booking_id = $booking->id;
            $conversation->save();

            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->user_id = Auth::User()->id;
            $message->text = 'Hi';
            $message->conversation_type = 'conversations';
            $message->save();

            //$this->chat($conversation->id);
            return response()->json(['link' => '/business/bookings?chat=' . $conversation->id]);
        }
    }

    public function send(Request $request)
    {
        Chat::sendConversationMessage($request->input('conversationId'), $request->input('text'));
    }

    public function groupSend(Request $request)
    {
        Chat::sendGroupConversationMessage($request->input('groupConversationId'), $request->input('text'));
    }

    public function sendFilesInConversation(Request $request)
    {
        Chat::sendFilesInConversation($request->input('conversationId') , $request->file('files'));
    }

    public function sendFilesInGroupConversation(Request $request)
    {
        Chat::sendFilesInGroupConversation($request->input('groupConversationId') , $request->file('files'));
    }
    public function routeAcceptMessage($id)
    {
        Chat::acceptMessageRequest($id);
        return redirect()->back();
    }
    public function routeTrigger(Request $request, $id)
    {
        Chat::startVideoCall($id , $request->all());
    }
    public function routegroupChatLeave($id)
    {
        Chat::leaveFromGroupConversation($id);
    }

}