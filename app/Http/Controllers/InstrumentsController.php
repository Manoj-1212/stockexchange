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
use DateTime;
use Illuminate\Support\Facades\Log;


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
            ->where('instruments.expiry','<',date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-t')))))
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
            ->where('instruments.expiry','>',date('Y-m-d'))
            ->where('instruments.expiry','<',date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-t')))))
            ->select('instruments.*')
            ->get();
        
        return response()->json(['status' => true, 'instruments' => $instruments, 'favourites' => $favouritesList]);

    }

    function buy_sell(Request $request){

        $dt= date('Y-m-d');
        $dt1 = strtotime($dt);
        $dt2 = date("l", $dt1);
        $dt3 = strtolower($dt2);
        
        if(($dt3 == "saturday" ) || ($dt3 == "sunday"))
            {
                return response()->json(['status' => false, 'message' => "Market Not Open"]);
            } 
        
        $exchnage_type = Instruments::where('instrument_token', $request->instrument_token)->first()->is_NFO_MCX();
        $instrument_details = Instruments::where('instrument_token', $request->instrument_token)->get();
        $instrument_details = json_decode($instrument_details,true);

        $current_time = date('h:i a');
        if($exchnage_type == 1){
            $sunrise = "9:15 am";
            $sunset = "3:30 pm";
        } else {
            $sunrise = "9:00 am";
            $sunset = "11:30 pm";
        }
        $date1 = DateTime::createFromFormat('h:i a', $current_time);
        $date2 = DateTime::createFromFormat('h:i a', $sunrise);
        $date3 = DateTime::createFromFormat('h:i a', $sunset);
        if ($date1 < $date2 || $date1 > $date3)
        {
           return response()->json(['status' => false, 'message' => "Market Not Open"]);
        }

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


        if($exchnage_type == 1){
            $usermargin = ($data['amount'] * $data['quantity'])/$brokerDetails['nfo_leverage'];
            $holdingbalance = ($data['amount'] * $data['quantity']) / $brokerDetails['nfo_holding'];
        } else {
            $usermargin = ($data['amount'] * $instrument_details[0]['lot_size'] * $data['quantity'])/$brokerDetails['mcx_leverage'];
            $holdingbalance = ($data['amount'] * $data['quantity'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
        }

        if($user['fund_balance'] < $usermargin) {
            return response()->json(['status' => false, 'message' => "Low wallet balance. Margin Short By ".$usermargin]);
        }

        $sellexists = false;
        $buyexists = false;

        if($data['action'] == 1) {

            $sellcount = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where("order_checkout.user_id","=",$user['id'])
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 2)
                ->count();
            if($sellcount > 0){
                $sellexists = true;
                    $selldetails = DB::table('order_checkout')
                        ->where('order_checkout.status', '=', 0)
                        ->where("order_checkout.user_id","=",$user['id'])
                        ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                        ->where('order_checkout.action', '=', 2)
                        ->select('*')
                        ->orderby('id','asc')
                        ->get();
                    $selldetails = json_decode($selldetails,true);
                    $quantity = $data['quantity'];
                    foreach($selldetails as $row){
                        if($quantity >= $row['qty']){

                        if($exchnage_type == 1){
                        $brokerage = ((($row['amount'] + $data['amount']) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($row['amount'] - $data['amount'])*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                        } else {
                        $brokerage = (((($row['amount'] * $instrument_details[0]['lot_size']) + ($data['amount'] * $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($row['amount'] - $data['amount'])*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        }


                        $balance = $user['fund_balance'] + $actualprofit + $row['margin'];
                        DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                        $new = new Funds;
                        $new->user_id = $user['id'];
                        $new->amount = $actualprofit + $row['margin'];
                        $new->status = 1;
                        $new->save();


                        $new = new Brokerage;
                        $new->user_id = $user['id'];
                        $new->order_id = $row['id'];
                        $new->instrument_id = $data['instrument_id'];
                        $new->brokerage = $brokerage;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        $new = new ProfitLoss;
                        $new->user_id = $user['id'];
                        $new->order_id = $row['id'];
                        $new->instrument_id = $data['instrument_id'];
                        $new->profit = $profit;
                        $new->actual_profit = $actualprofit;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2,'processed_amount' => $data['amount'],'processed_date' => date('Y-m-d H:i:s')));

                        $quantity = $quantity - $row['qty'];
                        } else if($quantity < $row['qty'] && $quantity > 0){
                            $margin = ($row['margin']/$row['qty'])*$quantity;
                            $holding = ($row['holding_balance']/$row['qty'])*$quantity;

                            $Order = new Order;
                            $Order->instrument_id = $data['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $data['amount'];
                            $Order->total_amount = round($data['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $data['order_type'];
                            $Order->action = 1;
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $margin;
                            $Order->holding_balance = $holding;
                            $Order->instrument_details = $data['instrument_details'];
                            $Order->status = 0;
                            $Order->save();


                            if($exchnage_type == 1){
                        $brokerage = ((($row['amount'] + $data['amount']) * $quantity)/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($row['amount'] - $data['amount'])*$quantity;
                        $actualprofit = $profit - $brokerage;
                        } else {
                        $brokerage = (((($row['amount'] * $instrument_details[0]['lot_size']) + ($data['amount'] * $instrument_details[0]['lot_size'])) * $quantity)/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($row['amount'] - $data['amount'])*$quantity*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        }


                        $balance = $user['fund_balance'] + $actualprofit + $margin;
                        DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                        $new = new Funds;
                        $new->user_id = $user['id'];
                        $new->amount = $actualprofit + $margin;
                        $new->status = 1;
                        $new->save();


                        $new = new Brokerage;
                        $new->user_id = $user['id'];
                        $new->order_id = $Order->id;
                        $new->instrument_id = $data['instrument_id'];
                        $new->brokerage = $brokerage;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        $new = new ProfitLoss;
                        $new->user_id = $user['id'];
                        $new->order_id = $Order->id;
                        $new->instrument_id = $data['instrument_id'];
                        $new->profit = $profit;
                        $new->actual_profit = $actualprofit;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        DB::table('order_checkout')->
                        where('id', $Order->id)->
                        update(array('status' => 2,'processed_amount' => $row['amount'],'processed_date' => $row['created_at']));


                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('qty' => $row['qty'] - $quantity,'margin' => $row['margin'] - $margin , 'holding_balance' =>  $row['holding_balance'] - $holding, 'remaining_qty' =>  $row['qty'] - $quantity, 'total_amount' => $row['amount'] * ($row['qty'] - $quantity) ));


                        $quantity = 0;
                        } 
                    }
                    if($quantity != 0){

                            if($exchnage_type == 1){
                                $usermargin = ($data['amount'] * $quantity)/$brokerDetails['nfo_leverage'];
                                $holdingbalance = ($data['amount'] * $quantity) / $brokerDetails['nfo_holding'];
                            } else {
                                $usermargin = ($data['amount'] * $instrument_details[0]['lot_size'] * $quantity)/$brokerDetails['mcx_leverage'];
                                $holdingbalance = ($data['amount'] * $quantity * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                            }

                            $Order = new Order;
                            $Order->instrument_id = $data['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $data['amount'];
                            $Order->total_amount = round($data['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $data['order_type'];
                            $Order->action = $data['action'];
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $usermargin;
                            $Order->holding_balance = $holdingbalance;
                            $Order->instrument_details = $data['instrument_details'];
                            $Order->status = 0;
                            $Order->save();

                                DB::table('users')->
                                    where('id', $user['id'])->
                                    update(array('fund_balance' => $user['fund_balance'] - $usermargin));

                                $new = new Funds;
                                $new->user_id = $user['id'];
                                $new->amount = $usermargin;
                                $new->status = 2;
                                $new->save();

                    }

            }

            
        } else if($data['action'] == 2) {
            $buycount = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where("order_checkout.user_id","=",$user['id'])
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->count();

            if($buycount > 0){

                    $buyexists = true;
            

                    $buydetails = DB::table('order_checkout')
                    ->where('order_checkout.status', '=', 0)
                    ->where("order_checkout.user_id","=",$user['id'])
                    ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                    ->where('order_checkout.action', '=', 1)
                    ->select('*')
                    ->get();
                    $buydetails = json_decode($buydetails,true);
                    $quantity = $data['quantity'];
                    foreach($buydetails as $row){

                    if($quantity >= $row['qty']){

                        if($exchnage_type == 1){
                        $brokerage = ((($data['amount'] + $row['amount']) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($data['amount'] - $row['amount'])*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                        } else {
                        $brokerage = (((($row['amount'] * $instrument_details[0]['lot_size']) + ($data['amount'] * $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($data['amount'] - $row['amount'])*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        }


                        $balance = $user['fund_balance'] + $actualprofit + $row['margin'];
                        DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                        $new = new Funds;
                        $new->user_id = $user['id'];
                        $new->amount = $actualprofit + $row['margin'];
                        $new->status = 1;
                        $new->save();


                        $new = new Brokerage;
                        $new->user_id = $user['id'];
                        $new->order_id = $row['id'];
                        $new->instrument_id = $data['instrument_id'];
                        $new->brokerage = $brokerage;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        $new = new ProfitLoss;
                        $new->user_id = $user['id'];
                        $new->order_id = $row['id'];
                        $new->instrument_id = $data['instrument_id'];
                        $new->profit = $profit;
                        $new->actual_profit = $actualprofit;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2,'processed_amount' => $data['amount'],'processed_date' => date('Y-m-d H:i:s')));

                        $quantity = $quantity - $row['qty'];

                        } else if($quantity < $row['qty'] && $quantity > 0){

                            $margin = ($row['margin']/$row['qty'])*$quantity;
                            $holding = ($row['holding_balance']/$row['qty'])*$quantity;

                            $Order = new Order;
                            $Order->instrument_id = $data['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $data['amount'];
                            $Order->total_amount = round($data['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $data['order_type'];
                            $Order->action = 2;
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $margin;
                            $Order->holding_balance = $holding;
                            $Order->instrument_details = $data['instrument_details'];
                            $Order->status = 0;
                            $Order->save();


                            if($exchnage_type == 1){
                        $brokerage = ((($data['amount'] + $row['amount']) * $quantity)/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($data['amount'] - $row['amount'])*$quantity;
                        $actualprofit = $profit - $brokerage;
                        } else {
                        $brokerage = (((($data['amount'] * $instrument_details[0]['lot_size']) + ($row['amount'] * $instrument_details[0]['lot_size'])) * $quantity)/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($data['amount'] - $row['amount'])*$quantity*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        }


                        $balance = $user['fund_balance'] + $actualprofit + $margin;
                        DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                        $new = new Funds;
                        $new->user_id = $user['id'];
                        $new->amount = $actualprofit + $margin;
                        $new->status = 1;
                        $new->save();


                        $new = new Brokerage;
                        $new->user_id = $user['id'];
                        $new->order_id = $Order->id;
                        $new->instrument_id = $data['instrument_id'];
                        $new->brokerage = $brokerage;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        $new = new ProfitLoss;
                        $new->user_id = $user['id'];
                        $new->order_id = $Order->id;
                        $new->instrument_id = $data['instrument_id'];
                        $new->profit = $profit;
                        $new->actual_profit = $actualprofit;
                        $new->exchange = $exchnage_type;
                        $new->broker_id = $brokerDetails['id'];
                        $new->save();

                        DB::table('order_checkout')->
                        where('id', $Order->id)->
                        update(array('status' => 2,'processed_amount' => $row['amount'],'processed_date' => $row['created_at']));


                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('qty' => $row['qty'] - $quantity,'margin' => $row['margin'] - $margin , 'holding_balance' =>  $row['holding_balance'] - $holding, 'remaining_qty' =>  $row['qty'] - $quantity, 'total_amount' => $row['amount'] * ($row['qty'] - $quantity) ));


                        $quantity = 0;
                        } 
                    }

                    if($quantity != 0){

                            if($exchnage_type == 1){
                                $usermargin = ($data['amount'] * $quantity)/$brokerDetails['nfo_leverage'];
                                $holdingbalance = ($data['amount'] * $quantity) / $brokerDetails['nfo_holding'];
                            } else {
                                $usermargin = ($data['amount'] * $instrument_details[0]['lot_size'] * $quantity)/$brokerDetails['mcx_leverage'];
                                $holdingbalance = ($data['amount'] * $quantity * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                            }

                            $Order = new Order;
                            $Order->instrument_id = $data['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $data['amount'];
                            $Order->total_amount = round($data['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $data['order_type'];
                            $Order->action = $data['action'];
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $usermargin;
                            $Order->holding_balance = $holdingbalance;
                            $Order->instrument_details = $data['instrument_details'];
                            $Order->status = 0;
                            $Order->save();

                                DB::table('users')->
                                    where('id', $user['id'])->
                                    update(array('fund_balance' => $user['fund_balance'] - $usermargin));

                                $new = new Funds;
                                $new->user_id = $user['id'];
                                $new->amount = $usermargin;
                                $new->status = 2;
                                $new->save();

                    }

            }
        }

        if(!$buyexists && !$sellexists){
            $Order = new Order;
            $Order->instrument_id = $data['instrument_id'];
            $Order->user_id = $user['id'];
            $Order->amount = $data['amount'];
            $Order->total_amount = round($data['amount'] * $data['quantity'],2);
            $Order->qty = $data['quantity'];
            $Order->remaining_qty = $data['quantity'];
            $Order->order_type = $data['order_type'];
            $Order->action = $data['action'];
            $Order->exchange = $exchnage_type;
            $Order->margin = $usermargin;
            $Order->holding_balance = $holdingbalance;
            $Order->instrument_details = $data['instrument_details'];
            if($data['order_type'] == 1) {
                $Order->status = 0;
            } else {
                exec("cd /var/www/html/kiteconnectjs-master && sudo forever restart examples/websocket.js");
                $Order->status = 1;
            }
            $Order->save();

            if($data['order_type'] == 1) {
                DB::table('users')->
                    where('id', $user['id'])->
                    update(array('fund_balance' => $user['fund_balance'] - $usermargin));

                $new = new Funds;
                $new->user_id = $user['id'];
                $new->amount = $usermargin;
                $new->status = 2;
                $new->save();

            }

        }
        return response()->json(['status' => true, 'message' => "$buySell Order Place Successfully !!"]);
        

    }


    function portfolio(Request $request){
        $user = JWTAuth::authenticate($this->token);
        $brokerDetails = $user->brokerDetail()->first();

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
            $kite_setting = json_decode($kite_setting,true);

        $profit = 0;
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 0)
            ->where('order_checkout.user_id', '=', $user['id'])
            ->orderby('order_checkout.created_at','DESC')
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i:%s") as formatted_date'),DB::raw('DATE_FORMAT(order_checkout.updated_at, "%M %d , %H:%i:%s") as updated_date'))
            ->get();
        $orderall = json_decode($orders,true); 
        $orders = json_decode($orders,true);
        $i=0;
        $instrument_details = array();
        $exchnage_type = 0;
        if(!empty($orderall)){
            foreach($orderall as $row) {
            $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();
            $instrument_details = Instruments::where('instrument_token', $row['instrument_id'])->get();
            $instrument_details = json_decode($instrument_details,true);

            $url = 'https://api.kite.trade/quote/ohlc?i='.$row['instrument_id'];
            $ch = curl_init();
            $curlConfig = array(
                    CURLOPT_URL => $url,
                    CURLOPT_HTTPGET => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array('Authorization: token '.$kite_setting[0]['api_key'].':'.$kite_setting[0]['access_token'])
                );
            curl_setopt_array($ch, $curlConfig);
            $result = curl_exec($ch);
            $data = json_decode($result,true);
            $last_price = $data['data'][$row['instrument_id']]['last_price'];  
            $orders[$i]['cmp'] = $last_price;
            curl_close($ch);


                if($row['action'] == 'Buy'){

                    if($exchnage_type == 1){
                        $profit += ($last_price - $row['amount'])*$row['qty'];
                    } else {
                        $profit += ($last_price - $row['amount'])*$row['qty']*$instrument_details[0]['lot_size'];
                    } 
                } else {
                    if($exchnage_type == 1){
                        $profit += ($row['amount'] - $last_price)*$row['qty'];
                    } else {
                        $profit += ($row['amount'] - $last_price)*$row['qty']*$instrument_details[0]['lot_size'];
                    }

                }
                
                    $i++;
            }
        }

        $margin = Order::select(DB::raw('SUM(margin) as marginAvailable'))->where('user_id', $user['id'])->where('status', 0)->first();
        $portfolio = ['ledgerBalance' => round($user['fund_balance'],2), 'marginAvailable' => round($user['fund_balance'] - ($margin->marginAvailable + $profit),2)  ,'activePl' => round($profit,2),'m2m' => round($user['fund_balance']+$profit,2)];

        return response()->json(['status' => true, 'portfolio' => $portfolio, 'orders' => $orders, 'lot_size' => !empty($instrument_details)?$instrument_details[0]['lot_size']:1, 'exchnage_type' => $exchnage_type]);

    }


    function trades(Request $request){

        list($start_date, $end_date) = $this->x_week_range(date('Y-m-d'));

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
            ->where('order_checkout.status', '=', 2)
            ->where("order_checkout.user_id","=",$user['id'])
            ->where('order_checkout.created_at', '>=', $start_date." 00:00:00")
            ->where('order_checkout.created_at', '<=', $end_date." 23:59:59")
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->leftjoin('profit_loss', 'profit_loss.order_id', 'order_checkout.id')
            ->leftjoin('brokerage', 'brokerage.order_id', 'order_checkout.id')
            ->orderby('order_checkout.created_at','DESC')
            ->select('order_checkout.*','profit_loss.profit','brokerage.brokerage','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i:%s") as formatted_date'),DB::raw('DATE_FORMAT(order_checkout.processed_date, "%M %d , %H:%i:%s") as processed_date'))
            ->get();
        } else if($trades[$data['trade_type']] == 1){
            $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [1,3])
            ->where("order_checkout.user_id","=",$user['id'])
            ->where('order_checkout.created_at', '>=', $start_date." 00:00:00")
            ->where('order_checkout.created_at', '<=', $end_date." 23:59:59")
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->orderby('order_checkout.created_at','DESC')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i:%s") as formatted_date'),DB::raw('DATE_FORMAT(order_checkout.updated_at, "%M %d , %H:%i:%s") as updated_date'))
            ->get();
        } else {
            $orders = DB::table('order_checkout')
            ->where("order_checkout.user_id","=",$user['id'])
            ->where('order_checkout.status', '=', $trades[$data['trade_type']])
            ->orderby('order_checkout.created_at','DESC')
            ->join('instruments', 'instruments.instrument_token', 'order_checkout.instrument_id')
            ->select('order_checkout.*','instruments.trading_symbol',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i:%s") as formatted_date'),DB::raw('DATE_FORMAT(order_checkout.updated_at, "%M %d , %H:%i:%s") as updated_date'))
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
        $brokerDetails = $user->brokerDetail()->first();

        $nse = ["brokerage" => '500 per crore', "margin_intraday" => 'Turnonver / '.$brokerDetails['nfo_leverage'], "margin_holding" => 'Turnonver / '.$brokerDetails['nfo_holding']];

        $mcx = ["brokerage" => '500 per crore', "margin_intraday" => 'Turnonver / '.$brokerDetails['mcx_leverage'], "margin_holding" => 'Turnonver / '.$brokerDetails['mcx_holding'], "exposure_type" => 'per_turnover', "brokerage_type" => 'per_crore'];
        
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

    function cancel_order(Request $request) {

        $user = JWTAuth::authenticate($this->token);

        $id = $request->id;
        DB::table('order_checkout')->
                where('id', $id)->
                update(array('status' => 3,'updated_at' => date('Y-m-d H:i:s')));
        exec("cd /var/www/html/kiteconnectjs-master && sudo forever restart examples/websocket.js");
        return response()->json(['status' => true, 'message' => "Trade is cancelled"]);
    }

    function x_week_range($date) {
    $ts = strtotime($date);
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    return array(date('Y-m-d', $start),
                 date('Y-m-d', strtotime('next saturday', $start)));
    }

    function close_order(Request $request) {

        $user = JWTAuth::authenticate($this->token);

        $id = $request->id;
        
        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
            $kite_setting = json_decode($kite_setting,true);

            $buydetails = DB::table('order_checkout')
                ->where('order_checkout.id', '=',$id)
                ->select('order_checkout.*')
                ->get();
                $buydetails = json_decode($buydetails,true);
            $row = $buydetails[0];

            $brokerDetails = $user->brokerDetail()->first();

            $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();
            $instrument_details = Instruments::where('instrument_token', $row['instrument_id'])->get();
            $instrument_details = json_decode($instrument_details,true);

            $url = 'https://api.kite.trade/quote/ohlc?i='.$row['instrument_id'];
            $ch = curl_init();
            $curlConfig = array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Authorization: token '.$kite_setting[0]['api_key'].':'.$kite_setting[0]['access_token'])
            );
            curl_setopt_array($ch, $curlConfig);
            $result = curl_exec($ch);
            $data = json_decode($result,true);
            $last_price = $data['data'][$row['instrument_id']]['last_price'];  
            curl_close($ch);
            if($row['action'] == 1){

                    if($exchnage_type == 1){
                        $brokerage = ((($last_price + $row['amount']) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($last_price - $row['amount'])*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                    } else {
                        $brokerage = (((($last_price * $instrument_details[0]['lot_size']) + ($row['amount'] * $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($last_price - $row['amount'])*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                    }
                } else {
                    if($exchnage_type == 1){
                        $brokerage = ((($row['amount'] + $last_price) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($row['amount'] - $last_price)*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                    } else {
                        $brokerage = (((($row['amount'] * $instrument_details[0]['lot_size']) + ($last_price * $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($row['amount'] - $last_price)*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                    }

                }
                    $balance = $user['fund_balance'] + $actualprofit + $row['margin'];
                    DB::table('users')->where('id', $row['user_id'])->update(array('fund_balance' => $balance));

                    $new = new Funds;
                    $new->user_id = $row['user_id'];
                    $new->amount = $actualprofit + $row['margin'];
                    $new->status = 1;
                    $new->save();


                    $new = new Brokerage;
                    $new->user_id = $row['user_id'];
                    $new->order_id = $row['id'];
                    $new->instrument_id = $row['instrument_id'];
                    $new->brokerage = $brokerage;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    $new = new ProfitLoss;
                    $new->user_id = $row['user_id'];
                    $new->order_id = $row['id'];
                    $new->instrument_id = $row['instrument_id'];
                    $new->profit = $profit;
                    $new->actual_profit = $actualprofit;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2,'processed_amount' => $last_price,'processed_date' => date('Y-m-d H:i:s')));

                        exec("cd /var/www/html/kiteconnectjs-master && sudo forever restart examples/websocket.js");
                    return response()->json(['status' => true, 'message' => "Trade Closed Successfully"]);

    }

    /*function buy_sell(Request $request){

        $dt= date('Y-m-d');
        $dt1 = strtotime($dt);
        $dt2 = date("l", $dt1);
        $dt3 = strtolower($dt2);
        
        if(($dt3 == "saturday" ) || ($dt3 == "sunday"))
            {
                return response()->json(['status' => false, 'message' => "Market Not Open"]);
            } 
        
        $exchnage_type = Instruments::where('instrument_token', $request->instrument_token)->first()->is_NFO_MCX();

        $current_time = date('h:i a');
        if($exchnage_type == 1){
            $sunrise = "9:15 am";
            $sunset = "3:30 pm";
        } else {
            $sunrise = "9:00 am";
            $sunset = "11:30 pm";
        }
        $date1 = DateTime::createFromFormat('h:i a', $current_time);
        $date2 = DateTime::createFromFormat('h:i a', $sunrise);
        $date3 = DateTime::createFromFormat('h:i a', $sunset);
        if ($date1 < $date2 || $date1 > $date3)
        {
           return response()->json(['status' => false, 'message' => "Market Not Open"]);
        }

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

        $instrument_details = Instruments::where('instrument_token', $request->instrument_token)->get();
        $instrument_details = json_decode($instrument_details,true);
        if($exchnage_type == 1){
            $usermargin = ($data['amount'] * $data['quantity'])/$brokerDetails['nfo_leverage'];
            $holdingbalance = ($data['amount'] * $data['quantity']) / $brokerDetails['nfo_holding'];
        } else {
            $usermargin = ($data['amount'] * $instrument_details[0]['lot_size'] * $data['quantity'])/$brokerDetails['mcx_leverage'];
            $holdingbalance = ($data['amount'] * $data['quantity'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
        }

        if($user['fund_balance'] < $usermargin) {
            return response()->json(['status' => false, 'message' => "Low wallet balance. Margin Short By ".$usermargin]);
        }

        $sellexists = false;
        $buyexists = false;

        if($data['action'] == 1) {

            $sellcount = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 2)
                ->count();
            if($sellcount > 0){
                    $sellexists = true;
            }

            $selldetails = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 2)
                ->select('*')
                ->get();
            $selldetails = json_decode($selldetails,true);

        } else {
            $buycount = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->count();

            if($buycount > 0){
                    $buyexists = true;
            }

            $buydetails = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$data['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->select('*')
                ->get();
            $buydetails = json_decode($buydetails,true);
        }


            $Order = new Order;
            $Order->instrument_id = $data['instrument_id'];
            $Order->user_id = $user['id'];
            $Order->amount = $data['amount'];
            $Order->total_amount = round($data['amount'] * $data['quantity'],2);
            $Order->qty = $data['quantity'];
            $Order->remaining_qty = $data['quantity'];
            $Order->order_type = $data['order_type'];
            $Order->action = $data['action'];
            $Order->exchange = $exchnage_type;
            $Order->margin = $usermargin;
            $Order->holding_balance = $holdingbalance;
            $Order->instrument_details = $data['instrument_details'];
            if($data['order_type'] == 1) {
                $Order->status = 0;
            } else {
                $Order->status = 1;
            }
            $Order->save();

            if($data['action'] == 1) {
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
                        update(array('status' => 0));
                    } else{
                    if($exchnage_type == 1){
                        $brokerage = ((($data['amount'] +  ($buy['amount']/$buy['buyqty'])) * $data['quantity'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($data['amount'] - ($buy['amount']/$buy['buyqty']))*$data['quantity'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $data['quantity']) / $brokerDetails['nfo_holding'];
                    } else {
                        $brokerage = (((($data['amount'] * $instrument_details[0]['lot_size']) +  (($buy['amount']/$buy['buyqty'])* $instrument_details[0]['lot_size'])) * $data['quantity'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($data['amount'] - ($buy['amount']/$buy['buyqty']))*$data['quantity']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $data['quantity'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
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
                        update(array('status' => 2,'processed_amount' => round(($buy['amount']/$buy['buyqty']),2),'processed_date' => date('Y-m-d H:i:s')));
                    
                    }

                }

            }

            return response()->json(['status' => true, 'message' => "$buySell Order Place Successfully !!"]); 

    }*/


}
