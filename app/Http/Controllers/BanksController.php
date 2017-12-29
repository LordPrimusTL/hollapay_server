<?php

namespace App\Http\Controllers;

use DB;
use App\User;
use App\Banks;
use Validator;
use Carbon\Carbon;
use App\Organization;
use App\Http\Requests;
use App\VerificationCodes;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BanksController extends Controller {

    public function get(Organization $CURRENT_USER) {
        $banks = Banks::where('organization_id', $CURRENT_USER->organization_id)->get();
        if (count($banks) > 0) {
            $status = "success";
            $message = "Bank Accounts successfully fetched";
            return response()->json(compact('status', 'message', 'banks'));
        } else {
            $status = "error";
            $message = "No bank accounts added";
            return response()->json(compact('status', 'message'));
        }
    }

    //################### ADD BANK ###################
    public function add(Request $request, Organization $organization) {
        //dd($request);
        //========== Save to Database ==========
        $bank = new Banks();
        $bank->organization_id = $organization->organization_id;
        $bank->bank_name = $request->input("bank_name");
        $bank->bank_code = $request->input("bank_code");
        $bank->acc_number = $request->input("acc_number");
        $bank->acc_name = $request->input("acc_name");
        $bank->save();

        $status = "success";
        $message = "Bank Account successfully added";
        return response()->json(compact('status', 'message', 'bank'));
    }

    //################### EDIT BANK ###################
    public function edit(Request $request, Organization $organization) {
        $bank = Banks::find($request->id);
        $bank->organization_id = $organization->organization_id;
        $bank->bank_name = $request->input("bank_name");
        $bank->bank_code = $request->input("bank_code");
        $bank->acc_number = $request->input("acc_number");
        $bank->acc_name = $request->input("acc_name");
        $bank->save();

        $status = "success";
        $message = "Bank account successfully edited";
        return response()->json(compact('status', 'message', 'bank'));
    }

    //################### DELETE BANK ###################
    public function delete(Request $request, Organization $CURRENT_USER) {
        $id = $request->input("id");
        Banks::where("id", $id)->delete();
        
        $status = "success";
        $message = "Bank successfully deleted";
        return response()->json(compact('status', 'message'));
    }

}
