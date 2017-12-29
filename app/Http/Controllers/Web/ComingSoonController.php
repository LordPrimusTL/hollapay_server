<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Subscriptions;

class ComingSoonController extends Controller {

    public function index() {
        return view('comming_soon');
    }

    public function subscribe(Request $request) {
        $subscription = new Subscriptions();
        $subscription->email = $request->input("email");
        $subscription->save();
        
        $success = "You have been successfully subscribed";
         return response()->json(compact('$success'));
    }

}
