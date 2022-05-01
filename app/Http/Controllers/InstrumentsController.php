<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Models\User;
use App\Models\Instruments;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Favourites;
use App\Models\Order;
use App\Models\Funds;


class InstrumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    protected $token;
 
    public function __construct(Request $request)
    {
        $this->token = $request->bearerToken();

    }

    public function get_user(Request $request)
    {
        $user = JWTAuth::authenticate($this->token);
 
        //return response()->json(['user' => $user]);
    }

    public function get_instruments_list(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $type = $request->type;
        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();

        $favourites = array_column(json_decode($favourites,true), 'instrument_token');

        $instruments = DB::table('instruments')
            ->whereNotIn('instrument_token', $favourites)
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();
        
        return response()->json(['status' => true, 'instruments' => $instruments]);  

    }


    public function get_favourites_list(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $type = $request->type;
        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();
    
        return response()->json(['status' => true, 'favourites' => $favourites]);  

    }


    public function save_favourites(Request $request){
        $user = JWTAuth::authenticate($this->token);

        $instruments = $request->instruments;
        $type = $request->type;
        foreach($instruments as $row){
            $new = new Favourites;
            $new->instrument_id = $row;
            $new->user_id = $user['id'];

            $new->save();
        }

        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();
        $favouritesList = $favourites;
        $favourites = array_column(json_decode($favourites,true), 'instrument_token');

        $instruments = DB::table('instruments')
            ->whereNotIn('instrument_token', $favourites)
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();
        
        return response()->json(['status' => true, 'instruments' => $instruments, 'favourites' => $favouritesList]);

    }

    function buy_sell(Request $request){
        $user = JWTAuth::authenticate($this->token);

        $data['instrument_id'] = $request->instrument_token;
        $data['quantity'] = $request->quantity;
        $data['amount'] = ($request->amount)?$request->amount:0;
        $data['order_type'] = $request->type;
        $data['action'] = $request->action;
        $data['instrument_details'] = $request->instrument_details;
        $buySell = $request->action == 1?"Buy":"Sell";

        //valid credential
        $validator = Validator::make($data, [
            'instrument_id' => 'required|integer',
            'quantity' => 'required'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['success' => false,'message' => $validator->messages()], 200);
        }

        $new = new Order;
        $new->instrument_id = $data['instrument_id'];
        $new->user_id = $user['id'];
        $new->amount = $data['amount'];
        $new->qty = $data['quantity'];
        $new->order_type = $data['order_type'];
        $new->action = $data['action'];
        $new->instrument_details = $data['instrument_details'];
        $new->save();

        return response()->json(['status' => true, 'message' => "$buySell Order Place Successfully !!"]);

    }


    function portfolio(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $portfolio = ['ledgerBalance' => 443, 'marginAvailable' => '0' ,'activePl' => 0,'m2m' => 523];

        return response()->json(['status' => true, 'portfolio' => $portfolio]);

    }


    function trades(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $data['trade_type'] = $request->type;

        //valid credential
        $validator = Validator::make($data, [
            'trade_type' => 'required|string'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['success' => false,'message' => $validator->messages()], 200);
        }

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', $trades[$data['trade_type']])
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'))
            ->get();

        return response()->json(['status' => true, 'orders' => $orders]);

    }

    function funds(Request $request){
        $user = JWTAuth::authenticate($this->token);
    
        $funds = DB::table('fund_balance')
            ->where('fund_balance.user_id', '=', $user['id'])
            ->select('fund_balance.amount', 'fund_balance.status','fund_balance.created_at', DB::raw('(CASE fund_balance.status WHEN 1 THEN "Credit" ELSE "Debit" END) as transaction_type'))
            ->get();

        return response()->json(['status' => true, 'funds' => $funds]);

    }

    function trading_profile(Request $request){
        $user = JWTAuth::authenticate($this->token);
        
        $nse = ["brokerage" => '500 per crore', "margin_intraday" => 'Turnonver / 400', "margin_holding" => 'Turnonver / 50'];

        $mcx = ["brokerage" => '500 per crore', "margin_intraday" => 'Turnonver / 400', "margin_holding" => 'Turnonver / 50', "exposure_type" => 'per_turnover', "brokerage_type" => 'per_crore'];
        
        return response()->json(['status' => true, 'nseTrading' => $nse, 'mcxTrading' => $mcx]);

    }




}
