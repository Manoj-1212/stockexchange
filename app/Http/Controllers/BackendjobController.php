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
use App\Models\TickerPrice;


class BackendjobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    protected $token;
 
    public function __construct(Request $request)
    {

    }


    public function excute_buy_order(Request $request){

        
        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 1)
            ->select('order_checkout.*')
            ->get();
        $orders = json_decode($orders,true); 
        foreach($orders as $row){

            $price = DB::table('ticker_price')
            ->where('ticker_price.instrument_id', '=', $row['instrument_id'])
            ->where('ticker_price.ltp', '<=', $row['amount'])
            ->where('ticker_price.created_date', '>', $row['created_at'])
            ->count();

            if($price > 0){

                $user = User::where('id', $row['user_id'])->first();
                $brokerDetails = $user->brokerDetail()->first();
                $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();

                $instrument_details = Instruments::where('instrument_token', $row['instrument_id'])->get();
                $instrument_details = json_decode($instrument_details,true);

                if($exchnage_type == 1){
                    $usermargin = ($row['amount'] * $row['qty'])/$brokerDetails['nfo_leverage'];
                } else {
                    $usermargin = ($row['amount'] * $instrument_details[0]['lot_size'] * $row['qty'])/$brokerDetails['mcx_leverage'];
                }

                $sellcount = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where("order_checkout.user_id","=",$user['id'])
                ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                ->where('order_checkout.action', '=', 2)
                ->count();

                if($sellcount == 0){
                    DB::table('order_checkout')->
                    where('id', $row['id'])->
                    update(array('status' => 0));

                    DB::table('users')->
                    where('id', $user['id'])->
                    update(array('fund_balance' => $user['fund_balance'] - $usermargin));

                    $new = new Funds;
                    $new->user_id = $user['id'];
                    $new->amount = $usermargin;
                    $new->status = 2;
                    $new->save();
                    exec("cd /var/www/html/kiteconnectjs-master && sudo forever restart examples/websocket.js");

                } else {

                    $selldetails = DB::table('order_checkout')
                        ->where('order_checkout.status', '=', 0)
                        ->where("order_checkout.user_id","=",$user['id'])
                        ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                        ->where('order_checkout.action', '=', 2)
                        ->select('*')
                        ->orderby('id','asc')
                        ->get();
                    $selldetails = json_decode($selldetails,true);
                    $quantity = $row['quantity'];


                    foreach($selldetails as $sell){
                        if($quantity >= $sell['qty']){

                            if($exchnage_type == 1){
                            $brokerage = ((($sell['amount'] + $row['amount']) * $sell['qty'])/100)*$brokerDetails['nfo_brokerage'];
                            $profit = ($sell['amount'] - $row['amount'])*$sell['qty'];
                            $actualprofit = $profit - $brokerage;
                            } else {
                            $brokerage = (((($sell['amount'] * $instrument_details[0]['lot_size']) + ($row['amount'] * $instrument_details[0]['lot_size'])) * $sell['qty'])/100)*$brokerDetails['mcx_brokerage'];
                            $profit = ($sell['amount'] - $row['amount'])*$sell['qty']*$instrument_details[0]['lot_size'];
                            $actualprofit = $profit - $brokerage;
                            }



                            $balance = $user['fund_balance'] + $actualprofit + $sell['margin'];
                            DB::table('users')->where('id', $user['id'])->update(array('fund_balance' => $balance));

                            $new = new Funds;
                            $new->user_id = $user['id'];
                            $new->amount = $actualprofit + $sell['margin'];
                            $new->status = 1;
                            $new->save();


                            $new = new Brokerage;
                            $new->user_id = $user['id'];
                            $new->order_id = $sell['id'];
                            $new->instrument_id = $row['instrument_id'];
                            $new->brokerage = $brokerage;
                            $new->exchange = $exchnage_type;
                            $new->broker_id = $brokerDetails['id'];
                            $new->save();

                            $new = new ProfitLoss;
                            $new->user_id = $user['id'];
                            $new->order_id = $sell['id'];
                            $new->instrument_id = $row['instrument_id'];
                            $new->profit = $profit;
                            $new->actual_profit = $actualprofit;
                            $new->exchange = $exchnage_type;
                            $new->broker_id = $brokerDetails['id'];
                            $new->save();

                            DB::table('order_checkout')->
                            where('id', $sell['id'])->
                            update(array('status' => 2,'processed_amount' => $row['amount'],'processed_date' => date('Y-m-d H:i:s')));

                            $quantity = $quantity - $sell['qty'];

                        } else if($quantity < $sell['qty'] && $quantity > 0){

                            $margin = ($sell['margin']/$sell['qty'])*$quantity;
                            $holding = ($sell['holding_balance']/$sell['qty'])*$quantity;

                            $Order = new Order;
                            $Order->instrument_id = $row['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $row['amount'];
                            $Order->total_amount = round($row['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $row['order_type'];
                            $Order->action = 1;
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $margin;
                            $Order->holding_balance = $holding;
                            $Order->instrument_details = $row['instrument_details'];
                            $Order->status = 0;
                            $Order->save();

                            if($exchnage_type == 1){
                            $brokerage = ((($sell['amount'] + $row['amount']) * $quantity)/100)*$brokerDetails['nfo_brokerage'];
                            $profit = ($sell['amount'] - $row['amount'])*$quantity;
                            $actualprofit = $profit - $brokerage;
                            } else {
                            $brokerage = (((($sell['amount'] * $instrument_details[0]['lot_size']) + ($row['amount'] * $instrument_details[0]['lot_size'])) * $quantity)/100)*$brokerDetails['mcx_brokerage'];
                            $profit = ($sell['amount'] - $row['amount'])*$quantity*$instrument_details[0]['lot_size'];
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
                            $new->instrument_id = $row['instrument_id'];
                            $new->brokerage = $brokerage;
                            $new->exchange = $exchnage_type;
                            $new->broker_id = $brokerDetails['id'];
                            $new->save();

                            $new = new ProfitLoss;
                            $new->user_id = $user['id'];
                            $new->order_id = $Order->id;
                            $new->instrument_id = $row['instrument_id'];
                            $new->profit = $profit;
                            $new->actual_profit = $actualprofit;
                            $new->exchange = $exchnage_type;
                            $new->broker_id = $brokerDetails['id'];
                            $new->save();

                            DB::table('order_checkout')->
                            where('id', $Order->id)->
                            update(array('status' => 2,'processed_amount' => $sell['amount'],'processed_date' => $sell['created_at']));


                            DB::table('order_checkout')->
                            where('id', $sell['id'])->
                            update(array('qty' => $sell['qty'] - $quantity,'margin' => $sell['margin'] - $margin , 'holding_balance' =>  $sell['holding_balance'] - $holding, 'remaining_qty' =>  $sell['qty'] - $quantity, 'total_amount' => $sell['amount'] * ($sell['qty'] - $quantity) ));


                            $quantity = 0;

                        }


                        if($quantity != 0){

                            if($exchnage_type == 1){
                                $usermargin = ($row['amount'] * $quantity)/$brokerDetails['nfo_leverage'];
                                $holdingbalance = ($row['amount'] * $quantity) / $brokerDetails['nfo_holding'];
                            } else {
                                $usermargin = ($row['amount'] * $instrument_details[0]['lot_size'] * $quantity)/$brokerDetails['mcx_leverage'];
                                $holdingbalance = ($row['amount'] * $quantity * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                            }

                            $Order = new Order;
                            $Order->instrument_id = $row['instrument_id'];
                            $Order->user_id = $user['id'];
                            $Order->amount = $row['amount'];
                            $Order->total_amount = round($row['amount'] * $quantity,2);
                            $Order->qty = $quantity;
                            $Order->remaining_qty = $quantity;
                            $Order->order_type = $row['order_type'];
                            $Order->action = $row['action'];
                            $Order->exchange = $exchnage_type;
                            $Order->margin = $usermargin;
                            $Order->holding_balance = $holdingbalance;
                            $Order->instrument_details = $row['instrument_details'];
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
                
                return response()->json(['status' => true, 'message' => "Request Processed"]);
            } 
        }

    }


    public function excute_buy_order_day_end(Request $request){

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [0,1] )
            ->where('order_checkout.action', '=', 1)
            ->where('order_checkout.exchange', '=', 1)
            ->select('order_checkout.*')
            ->limit(10)->orderBy('id', 'ASC')->get();
        $orders = json_decode($orders,true); 
        foreach($orders as $row){
            $user = User::where('id', $row['user_id'])->first();
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
            $high_price = $data['data'][$row['instrument_id']]['ohlc']['high'];  
            $low_price = $data['data'][$row['instrument_id']]['ohlc']['low'];   
            curl_close($ch);

            if($row['status'] == 0){
                    if($exchnage_type == 1){
                        $holdingbalance = ($row['amount'] * $row['qty']) / $brokerDetails['nfo_holding'];
                    } else {
                        $holdingbalance = ($row['amount'] * $row['qty'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                    }

                    $selldetails = DB::table('order_checkout')
                    ->where('order_checkout.status', '=', 2)
                    ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                    ->where('order_checkout.action', '=', 2)
                    ->where('order_checkout.created_at', 'like', "%".date('Y-m-d')."%")
                    ->select('order_checkout.id')
                    ->get();
                    $selldetails = json_decode($selldetails,true); 
                    if(empty($selldetails) && $user['fund_balance'] > $holdingbalance){
                        echo "keep Stock";
                    } else if(empty($selldetails) && $user['fund_balance'] < $holdingbalance){
                        $this->excute_sell_order_settlement($row['id']);
                    } else {
                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2,'processed_amount' => $last_price,'processed_date' => date('Y-m-d H:i:s')));
                    }
            }

            if($row['status'] == 1){
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 3));
            } 
        }

    }


    public function excute_buy_order_day_end_mcx(Request $request){

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [0,1] )
            ->where('order_checkout.action', '=', 1)
            ->where('order_checkout.exchange', '=', 2)
            ->select('order_checkout.*')
            ->limit(10)->orderBy('id', 'ASC')->get();
        $orders = json_decode($orders,true); 
        foreach($orders as $row){
            $user = User::where('id', $row['user_id'])->first();
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
            $high_price = $data['data'][$row['instrument_id']]['ohlc']['high'];  
            $low_price = $data['data'][$row['instrument_id']]['ohlc']['low'];   
            curl_close($ch);

            if($row['status'] == 0){
                    if($exchnage_type == 1){
                        $holdingbalance = ($row['amount'] * $row['qty']) / $brokerDetails['nfo_holding'];
                    } else {
                        $holdingbalance = ($row['amount'] * $row['qty'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                    }

                    $selldetails = DB::table('order_checkout')
                    ->where('order_checkout.status', '=', 2)
                    ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                    ->where('order_checkout.action', '=', 2)
                    ->where('order_checkout.created_at', 'like', "%".date('Y-m-d')."%")
                    ->select('order_checkout.id')
                    ->get();
                    $selldetails = json_decode($selldetails,true); 
                    if(empty($selldetails) && $user['fund_balance'] > $holdingbalance){
                        echo "keep Stock";
                    } else if(empty($selldetails) && $user['fund_balance'] < $holdingbalance){
                        $this->excute_sell_order_settlement($row['id']);
                    } else {
                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2,'processed_amount' => $last_price,'processed_date' => date('Y-m-d H:i:s')));
                    }
            }

            if($row['status'] == 1){
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 3));
            } 
        }

    }

    public function excute_sell_order(Request $request){

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 2)
            //->where('order_checkout.user_id', '=', $user['id'])
            ->select('order_checkout.*')
            ->get();
        $orders = json_decode($orders,true); 

        foreach($orders as $row){
            $user = User::where('id', $row['user_id'])->first();
            $brokerDetails = $user->brokerDetail()->first();

            $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();

            $instrument_details = Instruments::where('instrument_token', $row['instrument_id'])->get();
            $instrument_details = json_decode($instrument_details,true);


            $price = DB::table('ticker_price')
            ->where('ticker_price.instrument_id', '=', $row['instrument_id'])
            ->where('ticker_price.ltp', '>=', $row['amount'])
            ->where('ticker_price.created_date', '>', $row['created_at'])
            ->count();

            if($price > 0){

                $buydetails = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->select(DB::raw('SUM(order_checkout.qty) as buyqty'), DB::raw('SUM(order_checkout.total_amount) as amount'),DB::raw('SUM(order_checkout.margin) as margin'))
                ->get();
                $buydetails = json_decode($buydetails,true); 
                
                foreach($buydetails as $buy) {
                    if($buy['amount'] == '' && $buy['buyqty'] == ''){
                        DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 0));
                    } else {
                    if($exchnage_type == 1){
                        $brokerage = ((($row['amount'] +  ($buy['amount']/$buy['buyqty'])) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($row['amount'] - ($buy['amount']/$buy['buyqty']))*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $row['qty']) / $brokerDetails['nfo_holding'];
                    } else {
                        $brokerage = (((($row['amount'] * $instrument_details[0]['lot_size']) +  (($buy['amount']/$buy['buyqty'])* $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($row['amount'] - ($buy['amount']/$buy['buyqty']))*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $row['qty'] * $instrument_details[0]['lot_size']) / $brokerDetails['mcx_holding'];
                    }

                    $balance = $user['fund_balance'] + $actualprofit + $buy['margin'];
                    DB::table('users')->where('id', $row['user_id'])->update(array('fund_balance' => $balance));

                    $new = new Funds;
                    $new->user_id = $row['user_id'];
                    $new->amount = $actualprofit + $buy['margin'];
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
                        update(array('status' => 2,'processed_amount' => round(($buy['amount']/$buy['buyqty']),2),'processed_date' => date('Y-m-d H:i:s')));

                        exec("cd /var/www/html/kiteconnectjs-master && sudo forever restart examples/websocket.js");
                }
                       
                }
           
            }

        }


    }


public function excute_sell_order_day_end(Request $request){

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 2)
            ->where('order_checkout.exchange', '=', 1)
            ->select('order_checkout.*',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->limit(10)->orderBy('id', 'ASC')->get();
        $orders = json_decode($orders,true); 

        foreach($orders as $row){

            DB::table('order_checkout')->
                    where('id', $row['id'])->
                    update(array('status' => 3));
            
        }


    }


public function excute_sell_order_day_end_mcx(Request $request){

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 2)
            ->where('order_checkout.exchange', '=', 2)
            ->select('order_checkout.*',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->limit(10)->orderBy('id', 'ASC')->get();
        $orders = json_decode($orders,true); 

        foreach($orders as $row){

            DB::table('order_checkout')->
                    where('id', $row['id'])->
                    update(array('status' => 3));
            
        }


    }


public function excute_sell_order_settlement($orderid){

            $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
            $kite_setting = json_decode($kite_setting,true);

            $buydetails = DB::table('order_checkout')
                ->where('order_checkout.id', '=',$orderid)
                ->select('order_checkout.*')
                ->get();
                $buydetails = json_decode($buydetails,true);
            $row = $buydetails[0];
            $user = User::where('id', $row['user_id'])->first();
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
                

                    if($exchnage_type == 1){
                        $brokerage = ((($last_price + $row['amount']) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($last_price - $row['amount'])*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                    } else {
                        $brokerage = (((($last_price * $instrument_details[0]['lot_size']) + ($row['amount'] * $instrument_details[0]['lot_size'])) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($last_price - $row['amount'])*$row['qty']*$instrument_details[0]['lot_size'];
                        $actualprofit = $profit - $brokerage;
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
                   

    }


}
