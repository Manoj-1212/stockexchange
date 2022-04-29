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

        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->select('instruments.*')
            ->get();

        $favourites = array_column(json_decode($favourites,true), 'instrument_token');

        $instruments = DB::table('instruments')
            ->whereNotIn('instrument_token', $favourites)
            ->select('instruments.*')
            ->get();
        
        return response()->json(['status' => 'true', 'instruments' => $instruments]);  

    }


    public function get_favourites_list(Request $request){
        $user = JWTAuth::authenticate($this->token);

        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->select('instruments.*')
            ->get();
    
        return response()->json(['status' => 'true', 'favourites' => $favourites]);  

    }


    public function save_favourites(Request $request){
        $user = JWTAuth::authenticate($this->token);

        $instruments = $request->instruments;
        foreach($instruments as $row){
            $new = new Favourites;
            $new->instrument_id = $row;
            $new->user_id = $user['id'];

            $new->save();
        }

        $favourites = DB::table('instruments')
            ->join('favourites', 'favourites.instrument_id', 'instruments.instrument_token')
            ->where('favourites.user_id', '=', $user['id'])
            ->select('instruments.*')
            ->get();

        $favourites = array_column(json_decode($favourites,true), 'instrument_token');

        $instruments = DB::table('instruments')
            ->whereNotIn('instrument_token', $favourites)
            ->select('instruments.*')
            ->get();
        
        return response()->json(['status' => 'true', 'instruments' => $instruments]);

    }


}
