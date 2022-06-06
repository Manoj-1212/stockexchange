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
use App\Models\Brokerage;
use App\Models\ProfitLoss;


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
            ->where('instruments.expiry','>',date('Y-m-d'))
            ->select('instruments.*')
            ->get();

        $favourites = array_column(json_decode($favourites,true), 'instrument_token');

        $instruments = DB::table('instruments')
            ->whereNotIn('instrument_token', $favourites)
            ->where('instruments.exchange', '=', $type)
            ->where('instruments.expiry','>',date('Y-m-d'))
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
            ->where('instruments.expiry','>',date('Y-m-d'))
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
        $brokerDetails = $user->brokerDetail()->first();

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

        $exchnage_type = Instruments::where('instrument_token', $data['instrument_id'])->first()->is_NFO_MCX();
        if($exchnage_type == 1){
            $usermargin = ($data['amount'] * $data['quantity'])/$brokerDetails['nfo_leverage'];
        } else {
            $usermargin = ($data['amount'] * 100 * $data['quantity'])/$brokerDetails['mcx_leverage'];
        }

        if($user['fund_balance'] > $usermargin) {
            $Order = new Order;
            $Order->instrument_id = $data['instrument_id'];
            $Order->user_id = $user['id'];
            $Order->amount = $data['amount'];
            $Order->total_amount = round($data['amount'] * $data['quantity'],2);
            $Order->qty = $data['quantity'];
            $Order->order_type = $data['order_type'];
            $Order->action = $data['action'];
            $Order->exchange = $exchnage_type;
            $Order->margin = $usermargin;
            $Order->instrument_details = $data['instrument_details'];
            if($data['order_type'] == 1) {
                $Order->status = 0;
            } else {
                $Order->status = 1;
            }
            $Order->save();

            if($data['action'] == 1 && $data['order_type'] == 1) {
                DB::table('users')->
                    where('id', $user['id'])->
                    update(array('fund_balance' => $user['fund_balance'] - $usermargin));

                $new = new Funds;
                $new->user_id = $user['id'];
                $new->amount = $usermargin;
                $new->status = 2;
                $new->save();

            }

            if($data['action'] == 2 && $data['order_type'] == 1) {
                
                $buydetails = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->where('order_checkout.created_at', 'like', "%".date('Y-m-d')."%")
                ->select(DB::raw('SUM(order_checkout.qty) as buyqty'), DB::raw('SUM(order_checkout.total_amount) as amount'),DB::raw('SUM(order_checkout.margin) as margin'))
                ->get();
                $buydetails = json_decode($buydetails,true); 

                foreach($buydetails as $buy) {
                    if($buy['amount'] == '' && $buy['buyqty'] == ''){
                        DB::table('order_checkout')->
                        where('id', $Order->id)->
                        update(array('status' => 1));
                    } else{
                    if($exchnage_type == 1){
                        $brokerage = ((($data['amount'] +  ($buy['amount']/$buy['buyqty'])) * $data['quantity'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($data['amount'] - ($buy['amount']/$buy['buyqty']))*$data['quantity'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $data['quantity']) / $brokerDetails['nfo_holding'];
                    } else {
                        $brokerage = (((($data['amount'] * 100) +  (($buy['amount']/$buy['buyqty'])* 100)) * $data['quantity'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($data['amount'] - ($buy['amount']/$buy['buyqty']))*$data['quantity']*100;
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $data['quantity'] * 100) / $brokerDetails['mcx_holding'];
                    }


                    $balance = $user['fund_balance'] + $actualprofit + $buy['margin'];
                    DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                    $new = new Funds;
                    $new->user_id = $user['id'];
                    $new->amount = $actualprofit + $buy['margin'];
                    $new->status = 1;
                    $new->save();


                    $new = new Brokerage;
                    $new->user_id = $user['id'];
                    $new->instrument_id = $data['instrument_id'];
                    $new->brokerage = $brokerage;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    $new = new ProfitLoss;
                    $new->user_id = $user['id'];
                    $new->instrument_id = $data['instrument_id'];
                    $new->profit = $profit;
                    $new->actual_profit = $actualprofit;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    DB::table('order_checkout')->
                        where('id', $Order->id)->
                        update(array('status' => 2));
                    
                    }

                }

            }

            return response()->json(['status' => true, 'message' => "$buySell Order Place Successfully !!"]);
        } else {
            return response()->json(['status' => false, 'message' => "Low wallet balance"]);
        }
        

    }


    function portfolio(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $portfolio = ['ledgerBalance' => $user['fund_balance'], 'marginAvailable' => '0' ,'activePl' => 0,'m2m' => 523];

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
        if($trades[$data['trade_type']] == 2){
            $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [2,3])
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->orderby('order_checkout.created_at','DESC')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->get();
        } else {
            $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', $trades[$data['trade_type']])
            ->orderby('order_checkout.created_at','DESC')
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->get();
        }
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

    function remove_favourites(Request $request){
        $user = JWTAuth::authenticate($this->token);
        
        $instrument_token = $request->instrument_token;
        DB::table('favourites')->where('instrument_id',$instrument_token)->where('user_id',$user['id'])->delete();

        $type = $request->type;

        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->where('instruments.exchange', '=', $type)
            ->select('instruments.*')
            ->get();
    
        return response()->json(['status' => true, 'favourites' => $favourites]);

    }


}
