<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\User;
use Validator;
use App\Tokens;
use Carbon\Carbon;
use App\Notifications;
use GuzzleHttp\Client;
use App\Http\Requests;
use App\VerificationCodes;
use Illuminate\Http\Request;
use Meng\AsyncSoap\Guzzle\Factory;
use App\Http\Controllers\Controller;

class NotificationsController extends Controller {

    //######################### GET ALL NOTIFICATIONS #########################
    public function index(User $CURRENT_USER) {
        $notifications = Notifications::where("user_id", $CURRENT_USER->user_id)->get();
        
        $status = "success";
        $message = "Notifications fetched";
        return response()->json(compact('status', 'message','notifications'));
    }
}
