<?php

use Illuminate\Http\Request;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
Route::post('login', 'AuthenticateController@authenticate');
Route::post('register/phone', array('uses' => 'AuthenticateController@registerPhone'));
Route::post('verify/phone', array('uses' => 'AuthenticateController@verifyPhone'));
Route::post('register/basic', array('uses' => 'AuthenticateController@registerBasicInfo'));


//################### ROUTE FOR BANKS ###################
Route::group(['middleware' => ['validateApiKey'], 'prefix' => 'banks'], function() {
    Route::post('', array('uses' => 'BanksController@get'));
    Route::post('add', array('uses' => 'BanksController@add'));
    Route::post('resolve', array('uses' => 'TransactionsController@getAccountName'));
    Route::post('edit', array('uses' => 'BanksController@edit'));
    Route::post('delete', array('uses' => 'BanksController@delete'));
});


//################### ROUTE FOR CARDS ###################
Route::group(['prefix' => 'cards', 'middleware' => ['validateApiKey']], function() {
    Route::post('', array('uses' => 'CardsController@index'));
    Route::post('add', array('uses' => 'CardsController@tokenize'));
    Route::post('tokenize', array('uses' => 'CardsController@add'));
    Route::post('delete', array('uses' => 'CardsController@delete'));
});


//################### ROUTE FOR TRANSACTIONS ###################
Route::group(['middleware' => ['validateApiKey'], 'prefix' => 'transactions'], function() {
    Route::post('/', array('uses' => 'TransactionsController@index'));
    Route::post('resolve', array('uses' => 'TransactionsController@getAccountName'));
    Route::post('/send', array('uses' => 'TransactionsController@send'));
    Route::post('/withdraw/bank', array('uses' => 'TransactionsController@withraw_bank'));
    Route::post('/withdraw/atm', array('uses' => 'TransactionsController@withraw_atm'));
    Route::post('/facts', array('uses' => 'TransactionsController@generateFacts'));
});


//################### ROUTE FOR NOTIFICATIONS ###################
Route::group(['middleware' => ['validateApiKey'], 'prefix' => 'notifications'], function() {
    Route::post('', array('uses' => 'NotificationsController@index'));
    Route::post('delete', array('uses' => 'NotificationsController@delete'));
});


//################### ROUTE FOR USERS ###################
Route::group(['middleware' => ['validateApiKey'], 'prefix' => 'users'], function() {
    Route::post('', array('uses' => 'NotificationsController@index'));
    Route::post('edit', array('uses' => 'UserController@edit'));
    Route::post('upload', array('uses' => 'UserController@uploadImage'));
});


//################### ROUTE FOR WALLET ###################
Route::group(['middleware' => ['validateApiKey'], 'prefix' => 'wallet'], function() {
    Route::post('deposit', array('uses' => 'WalletController@depositToWallet'));
});

Route::post('support', array('uses' => 'SupportController@help', 'middleware' => ['validateToken']));

//All Bout Paycode
Route::group(['prefix' => 'paycode/','middleware' => ['validateApiKey']], function (){
    Route::post('access','PaycodeController@getAccessToken');
    Route::post('generate','PaycodeController@generate');
    Route::post('status','PaycodeController@status');
    Route::post('cancel','PaycodeController@cancel');
});

//Bills Payment
Route::group(['prefix' => '/bills/', 'middleware' => ['validateApiKey']], function ()
{
    //Dstv
    Route::group(['prefix' => 'dstv/'], function()
    {
        Route::get('products','BillsController@dstvProduct');
        Route::post('addon','BillsController@dstvAddon');
        Route::post('inquiry','BillsController@dstvInquiry');
        Route::post('advice','BillsController@dstvAdvice');
        Route::post('query','BillsController@dstvQuery');
    });

    //Gotv
    Route::group(['prefix' => 'gotv/'], function()
    {
        Route::get('products','BillsController@gotvProduct');
        Route::post('inquiry','BillsController@gotvInquiry');
        Route::post('advice','BillsController@gotvAdvice');
        Route::post('query','BillsController@gotvQuery');

    });

    //Star Times
    Route::group(['prefix' => 'startimes/'], function()
    {
        Route::post('inquiry','BillsController@startimesInquiry');
        Route::post('advice','BillsController@startimesAdvice');
        Route::post('query','BillsController@startimesQuery');

    });
});