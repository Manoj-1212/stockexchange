<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Hash;
use Session;

class DashboardController extends Controller
{

    protected $token;
    protected $user;
 
    public function __construct(Request $request)
    {
        if ($request->session()->has('accessToken')) {
           $this->user = $request->session()->get('accessToken');
        } else {
            return redirect()->to('/')->send();;
        }

    }

    public function index()
    {
        return view('dashboard',["user" => $this->user]);
    }

}