<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use App\User;
use Carbon\Carbon;
use App\VerificationCodes;
use App\Tokens;
use GuzzleHttp\Client;
use App\Services\HollaPayService;

class AuthenticateController extends Controller {
    /*
     * ****************** RESPONSE CODES ******************
     * 100     -     Number not Verified
     * 110     -     Verified but no basic details has been inputted but number has been verified
     * 120     -     User Already exist and has been verified
     * 200     -     Completely Registered
     * */

    public function index() {
        // TODO: show users
    }

    public function registerPhone(Request $request, HollaPayService $hollapay) {
        $phone = $request->input('phone');
        $user = User::where("user_id", $phone)->first();

        if (count($user) <= 0) {//the user does not exist at all
            //Save user Data
            $user = new User();
            $user->user_id = $phone;
            $user->save();
            $status = 100;

            //Generate and store Verification Code
            $dt = Carbon::now();
            $expires = $dt->addMinutes(5);

            $v = new VerificationCodes();
            $v->user_id = $user->user_id;
            $v->code = str_random(6);
            $v->expires = $expires;
            $v->save();

            //code to send SMS would be here
            $this->sendCode($phone, $v->code, $hollapay);
            //$this->sendCode("09081552310", $v->code);
            return response()->json(compact('status'));
        }
        $status = $user->status;
        return response()->json(compact('status'));
    }

    public function registerBasicInfo(Request $request) {
        $user_id = $request->input('user_id');
        $user = User::where("user_id", $user_id)->first();

        if (count($user) <= 0) {//the user does not exist at all
            $error = "User does not exist";
            return response()->json(compact('error'));
        } else {
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = bcrypt($request->input('password'));
            $user->gender = $request->input('gender');
            $user->status = 200;
            $user->save();

            //this is not part of the columns 
            //but should be part of the response
            $user->token = $this->storeToken($user_id);
            return response()->json(compact('user'));
        }
    }

    public function verifyPhone(Request $request) {
        $user_id = $request->input('user_id');
        $code = $request->input('code');

        $res = VerificationCodes::where("user_id", $user_id)->first()->where("code", $code)->first();
        if (count($res) > 0) {
            $now = Carbon::now();
            $then = $res->expires;
            $then = Carbon::parse($then);
            $delay = $now->diffInMinutes($then);

            if ($delay > 5) {//code has expired
                $res->delete();
                $error = "Code has expired.   Please request for a new code";
                return response()->json(compact('error'));
            } else {//successfull
                $status = 110;
                $user = User::where("user_id", $user_id)->first();
                $user->status = $status;
                $user->save();
                return response()->json(compact('status'));
            }
        } else {
            return response()->json(["error" => "Invalid verification code. " . $res]);
        }
    }

    public function authenticate(Request $request) {
        $username = $request->input('username');
        $password = $request->input('password');


        if (Auth::attempt(['email' => $username, 'password' => $password])) {
            $user = Auth::user();
            $user->token = $this->storeToken($user->user_id);
            return response()->json(compact('token'));
        } else if (Auth::attempt(['user_id' => $username, 'password' => $password])) {
            $user = Auth::user();
            $user->token = $this->storeToken($username);
            return response()->json(compact('user'));
        } else {
            return response()->json(["error" => "Invalid login details " . $password]);
        }
    }

    public function register(Request $request) {
        //Save user Data
        $user = new User();
        $user->user_id = $request->input('phone');
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->gender = $request->input('gender');
        $user->save();

        //Generate and store Verification Code
        $dt = Carbon::now();
        $expires = $dt->addMinutes(5);

        $v = new VerificationCodes();
        $v->user_id = $user->user_id;
        $v->code = str_random(6);
        $v->expires = $expires;
        $v->save();

        //Send Verification Code to  the user
        $token = $this->storeToken($user->user_id);
        return response()->json(compact('token'));
    }

    function generateToken() {
        return str_random(32);
    }

    /* This method generates and stores the token 
      then returns the generated token */

    function storeToken($user_id) {
        $t = new Tokens();
        $t->user_id = $user_id;
        $t->token = $this->generateToken();
        $t->save();
        return $t->token;
    }

    function sendCode($to, $code, $hollapay) {

        $message = "You recently tried to register on HollaPay App.   Use " . $code . " as your OTP.   \nThis code would expire in 5 minutes";
        $url = "/sendsms?api_key=" . $hollapay->SMS_API_KEY .
                "&api_secret=" . $hollapay->SMS_API_SECRET .
                "&destination=" . $to .
                "&message=" . $message .
                "&source=" . $hollapay->SMS_SOURCE;

        $client = new Client(['base_uri' => 'https://www.kedesa.com']);
        $response = $client->request('GET', $url);
        $response = $response->getBody()->getContents();
    }

}
