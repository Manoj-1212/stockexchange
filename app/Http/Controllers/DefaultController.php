<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Hash;

class DefaultController extends Controller
{

    public function index()
    {
        $title = 'Login Page';
        $template = 'admin';
        return view('index', ['data' => $data]);
    }

}