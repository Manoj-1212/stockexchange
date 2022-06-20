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

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);
        
        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 1)
            ->select('order_checkout.*',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->get();
        $orders = json_decode($orders,true); 
        foreach($orders as $row){
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

            if(($row['amount'] >= $last_price - 4) && ($row['amount'] <= $last_price + 4)){
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 0));

                $user = User::where('id', $row['user_id'])->first();

                $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();
                    if($exchnage_type == 1){
                        $usermargin = ($row['amount'] * $row['quantity'])/$brokerDetails['nfo_leverage'];
                    } else {
                        $usermargin = ($row['amount'] * 100 * $row['quantity'])/$brokerDetails['mcx_leverage'];
                    }
                
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


    public function excute_buy_order_day_end(Request $request){

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [0,1] )
            ->where('order_checkout.action', '=', 1)
            ->select('order_checkout.*')
            ->get();
        $orders = json_decode($orders,true); 
        foreach($orders as $row){
            $user = User::where('id', $row['user_id'])->first();
            $brokerDetails = $user->brokerDetail()->first();
            $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();

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
                        $holdingbalance = ($row['amount'] * $row['qty'] * 100) / $brokerDetails['mcx_holding'];
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
                        update(array('status' => 2));
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

        $kite_setting = DB::table('kite_setting')->select('kite_setting.*')->get();
        $kite_setting = json_decode($kite_setting,true);

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 1)
            ->where('order_checkout.action', '=', 2)
            ->select('order_checkout.*')
            ->get();
        $orders = json_decode($orders,true); 

        foreach($orders as $row){
            $user = User::where('id', $row['user_id'])->first();
            $brokerDetails = $user->brokerDetail()->first();

            $exchnage_type = Instruments::where('instrument_token', $row['instrument_id'])->first()->is_NFO_MCX();

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

            if(($row['amount'] >= $last_price - 4) && ($row['amount'] <= $last_price + 4)){

                $buydetails = DB::table('order_checkout')
                ->where('order_checkout.status', '=', 0)
                ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
                ->where('order_checkout.action', '=', 1)
                ->select(DB::raw('SUM(order_checkout.qty) as buyqty'), DB::raw('SUM(order_checkout.total_amount) as amount'),DB::raw('SUM(order_checkout.margin) as margin'))
                ->get();
                $buydetails = json_decode($buydetails,true); 
                
                foreach($buydetails as $buy) {
                    if($buy['amount'] == '' && $buy['buyqty'] == ''){
                        echo "No Buy done";
                    } else {
                    if($exchnage_type == 1){
                        $brokerage = ((($row['amount'] +  ($buy['amount']/$buy['buyqty'])) * $row['qty'])/100)*$brokerDetails['nfo_brokerage'];
                        $profit = ($row['amount'] - ($buy['amount']/$buy['buyqty']))*$row['qty'];
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $row['qty']) / $brokerDetails['nfo_holding'];
                    } else {
                        $brokerage = (((($row['amount'] * 100) +  (($buy['amount']/$buy['buyqty'])* 100)) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($row['amount'] - ($buy['amount']/$buy['buyqty']))*$row['qty']*100;
                        $actualprofit = $profit - $brokerage;
                        $holdingbalance = (($buy['amount']/$buy['buyqty']) * $row['qty'] * 100) / $brokerDetails['mcx_holding'];
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
                    $new->instrument_id = $row['instrument_id'];
                    $new->brokerage = $brokerage;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    $new = new ProfitLoss;
                    $new->user_id = $row['user_id'];
                    $new->instrument_id = $row['instrument_id'];
                    $new->profit = $profit;
                    $new->actual_profit = $actualprofit;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2));
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
            ->select('order_checkout.*',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
            ->get();
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
                        $brokerage = (((($last_price * 100) + ($row['amount'] * 100)) * $row['qty'])/100)*$brokerDetails['mcx_brokerage'];
                        $profit = ($last_price - $row['amount'])*$row['qty']*100;
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
                    $new->instrument_id = $row['instrument_id'];
                    $new->brokerage = $brokerage;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    $new = new ProfitLoss;
                    $new->user_id = $row['user_id'];
                    $new->instrument_id = $row['instrument_id'];
                    $new->profit = $profit;
                    $new->actual_profit = $actualprofit;
                    $new->exchange = $exchnage_type;
                    $new->broker_id = $brokerDetails['id'];
                    $new->save();

                    DB::table('order_checkout')->
                        where('id', $row['id'])->
                        update(array('status' => 2));
                   

    }


}
