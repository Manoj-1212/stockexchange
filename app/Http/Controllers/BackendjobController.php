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
                CURLOPT_HTTPHEADER => array('Authorization: token bxgemb4liqbi58ki:RUrrLzz32fLzGGO4ckyECvOMK0rRm10E')
            );
            curl_setopt_array($ch, $curlConfig);
            $result = curl_exec($ch);
            $data = json_decode($result,true);
            $last_price = $data['data'][$row['instrument_id']]['last_price'];   
            $high_price = $data['data'][$row['instrument_id']]['ohlc']['high'];  
            $low_price = $data['data'][$row['instrument_id']]['ohlc']['low'];   
            curl_close($ch);

            if(($row['amount'] > $low_price) && ($row['amount'] < $high_price)){
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 2));
            } else {
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 3));
            }
        }

    }

    public function excute_sell_order(Request $request){

        $trades = Order::TRADE;
        
        $orders = DB::table('order_checkout')
            ->whereIn('order_checkout.status', [1,0])
            ->where('order_checkout.action', '=', 2)
            ->select('order_checkout.*',DB::raw('(CASE order_checkout.action WHEN 1 THEN "Buy" ELSE "Sell" END) as action'),DB::raw('(CASE order_checkout.order_type WHEN 1 THEN "Market" ELSE "Order" END) as order_type'),DB::raw('DATE_FORMAT(order_checkout.created_at, "%M %d , %H:%i") as formatted_date'))
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
                CURLOPT_HTTPHEADER => array('Authorization: token bxgemb4liqbi58ki:RUrrLzz32fLzGGO4ckyECvOMK0rRm10E')
            );
            curl_setopt_array($ch, $curlConfig);
            $result = curl_exec($ch);
            $data = json_decode($result,true);
            $last_price = $data['data'][$row['instrument_id']]['last_price'];
            $high_price = $data['data'][$row['instrument_id']]['ohlc']['high'];  
            $low_price = $data['data'][$row['instrument_id']]['ohlc']['low'];   
            curl_close($ch);

            $buydetails = DB::table('order_checkout')
            ->where('order_checkout.status', '=', 2)
            ->where('order_checkout.instrument_id', '=',$row['instrument_id'])
            ->where('order_checkout.action', '=', 1)
            ->where('order_checkout.created_at', 'like', "%".date('Y-m-d')."%")
            ->select(DB::raw('SUM(order_checkout.qty) as buyqty'), DB::raw('SUM(order_checkout.total_amount) as amount'),DB::raw('SUM(order_checkout.margin) as margin'))
            ->get();
            $buydetails = json_decode($buydetails,true); 
            
            foreach($buydetails as $buy) {
                if($buy['amount'] == '' && $buy['buyqty'] == ''){
                    DB::table('order_checkout')->
                    where('id', $row['id'])->
                    update(array('status' => 3));
                }else{
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

                if($user['fund_balance'] < $holdingbalance ) {
                DB::table('order_checkout')->
                where('id', $row['id'])->
                update(array('status' => 0));
                }else if(($row['amount'] > $low_price) || ($row['amount'] < $high_price)){

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





}
