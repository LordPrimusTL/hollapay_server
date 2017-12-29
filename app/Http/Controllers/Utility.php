<?php

/**
 * Created by PhpStorm.
 * User: LordRahl
 * Date: 3/17/17
 * Time: 11:02 PM
 */

namespace App\Http\Controllers;

use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Utility {

    /**
     * Utility constructor.
     */
    public function __construct() {
        
    }

    public static $BASE_URL = "http://104.236.197.85/public/";

    /**
     * @param $data
     * @return mixed
     */
    static function return200($data, $status = "success") {
        $msg = ['code' => 200, 'status' => $status, 'message' => $data];
        return $msg;
    }



    /**
     * @param $data
     * @return mixed
     */
    static function return500($data) {
        $msg = ['code' => 500, 'status' => 'error', 'message' => $data];
        return $msg;
    }

    /**
     * @param $data
     * @return mixed
     */
    static function return405($data) {
        $msg = ['code' => 405, 'status' => 'error', 'message' => $data];
        return $msg;
    }

    /**
     * @return mixed
     */
    static function returnError($message) {
        $msg = [
            'code' => 205,
            'status' => "failed",
            'message' => $message];
        return $msg;
    }
    static function responseError($message) {
        $msg = [
            'code' => 205,
            'status' => "failed",
            'message' => $message];
        return response()->json($msg);
    }

    static function responseBalance(){
        $msg = [
            'code' => 210,
            'status' => "failed",
            'message' => "Insufficient Balance."];
        return response()->json($msg);
    }
    static function responseSuccess($data, $status = "success") {
        $msg = ['code' => 200, 'status' => $status, 'message' => $data];
        return response()->json($msg);
    }

    /**
     * @param $msg
     * @return string
     */
    static function error($msg) {
        return json_encode($msg);
    }

    /**
     * @param $file
     * @return string
     */
    static function uploadItemPhoto($file) {

        ini_set('upload_max_filesize', '100000M');
        ini_set('post_max_size', '1000000');

        //we move the file and return the url
        $folder = Date('Y') . '/' . Date('M');
        $path = storage_path($folder);

        if (!file_exists($path)) {
            Storage::disk('local')->makeDirectory($folder, 0777);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move($path, $filename);
        return $folder . '/' . $filename;
    }

    /**
     * @param $items
     * @param $perPage
     * @return LengthAwarePaginator
     */
    static function paginate($items, $perPage) {
        if (is_array($items)) {
            $items = collect($items);
        }

        return new LengthAwarePaginator(
                $items->forPage(Paginator::resolveCurrentPage(), $perPage), $items->count(), $perPage, Paginator::resolveCurrentPage(), ['path' => Paginator::resolveCurrentPath()]
        );
    }

    static function addToGP($user_id, $action = "max") {
        $arr = [];
        $user = \App\User::where('id', $user_id)->first();
        if ($action == "mini") {
            $arr = [0.009, 0.01, 0.02, 0.03, 0.04, 0.05, 0.06, 0.07, 0.08];
        } else {
            $arr = [0.6, 0.7, 0.8, 0.9, 0.2, 0.3, 0.4, 0.5, 0.09];
        }
        $randomIndex = array_rand($arr);
        $increament = $arr[$randomIndex];
        if (!Auth::check()) {
//            return;
        }

        if ($user->gp >= 5) {
            $user->gp = 5.00;
            $user->save();
            return;
        }

        $user->gp += $increament;
        $user->save();
    }

    static function createTextImage($text) {
        header('Content-Type: image/png');

        // Create the image
        $im = imagecreatetruecolor(1900, 300);
        $color = rand(0, 255);
        $color2 = rand(0, 255);

        // Create some colors
        $fontColor = imagecolorallocate($im, 255, 255, 255);
        $background = imagecolorallocate($im, $color2, $color, $color);
        imagefilledrectangle($im, 0, 0, 1900, 300, $background);

        $font = '/home/fabuloxi/public_html/finco-assets/ttf/arial.ttf';

        // Add the text
        //imagettftext($im, 50, 0, 390, 170, $fontColor, $font, $text);

        ob_start();
        imagepng($im);

        // Capture the output
        $imagedata = ob_get_contents();

        // Clear the output buffer
        ob_end_clean();
        return $imagedata;
    }

    static function getCapitalLetters($str) {
        if (preg_match_all('/\b[A-Z]+\b/', $str, $matches)) {
            return implode('', $matches[0]);
        } else {
            return false;
        }
    }

}
