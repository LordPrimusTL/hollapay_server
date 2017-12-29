<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Mail\SupportEmail;
use Mail;

class SupportController extends Controller {

    public function help(Request $request, User $CURRENT_USER) {
        $subject = $request->input("subject");
        $message = $request->input("message");

        $email = new SupportEmail($CURRENT_USER, $subject, $message);
        Mail::to("agbontaenefe@gmail.com")->send($email);
        
        $success = "Your message has been sent";
        return response()->json(compact('success'));
    }

}
