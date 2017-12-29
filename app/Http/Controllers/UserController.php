<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\User;
use App\Tokens;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Http\Requests;
use App\VerificationCodes;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller {

    var $server = "http://hollapay.fincoapps.com";
    public function index() {
        // TODO: show users
    }

    public function edit(Request $request, User $CURRENT_USER) {

        $user = User::find($CURRENT_USER->id);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->gender = $request->input('gender');
        $user->save();
        $user->token = $request->input('token');

        //$user = User::where("user_id", $CURRENT_USER->user_id)->first();
        $status = "success";
        $message = "Found user";
        return response()->json(compact('status','message','user'));
    }

    public function uploadImage(Request $request, User $CURRENT_USER) {
        $destinationPath = public_path() . '/uploads/images';
        $filename = $CURRENT_USER->user_id . "_" . $request->file('file')->getClientOriginalName();
        $upload_success = $request->file('file')->move($destinationPath, $filename);
        
        
        //================ UPDATE USER DETAILS IN DB //================
        $user = User::find($CURRENT_USER->id);

        $user->image = "http://hollapay.fincoapps.com/public/uploads/images/".$filename;
        $user->save();
        $user->token = $request->input('token');

        $status = "success";
        $message = "Image uploaded";

        return response()->json(compact('status','message','user'));
    }

}
