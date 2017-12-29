<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Utility;
use Closure;
use App\ApiKey;
use App\Organization;

class ValidateApiKey {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        //return response()->json(Utility::returnError($request->all()));
        if(!$request->has('api_key')) {
            $error = 'The api_key parameter is absent in request';
            return response()->json(Utility::returnError($error));
        }
        else if (!$request->has('organization_id')) {
            $error = 'The organization_id parameter is absent in request';
            return response()->json(Utility::returnError($error));
        }

        $apiKey = ApiKey::where(["api_key" => $request->api_key, "organization_id" => $request->organization_id])->get();
        if (count($apiKey) > 0) {
            $organization = Organization::where('organization_id', $request->organization_id)->first();
            $request->route()->setParameter('organization', $organization);
            return $next($request);
        } else {
            $error = "You are not authorized";
            return response()->json(Utility::returnError($error));
        }
    }

}
