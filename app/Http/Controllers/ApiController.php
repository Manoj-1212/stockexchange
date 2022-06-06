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

class ApiController extends Controller
{

    public function register(Request $request)
    {
        //Validate data
        $data = $request->only('name', 'email', 'password');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone,
            'address' => $request->address,
            'role' => 'user',
            'fund_balance' => $request->wallet,
            'parent_id' => $request->broker_id,
            'status' => 1,
            'password' => bcrypt($request->password)
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ], Response::HTTP_OK);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['success' => false,'message' => $validator->messages()], 200);
        }

        //Request is validated
        //Crean token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 401);
            }
        } catch (JWTException $e) {
        return $credentials;
            return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
        }
    
        //Token created, return with success response and jwt token
        $user = auth()->user();
        if($user['role'] == 'user' && $user['status'] = 1){
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => auth()->user(),
                'tickerToken' => 'RUrrLzz32fLzGGO4ckyECvOMK0rRm10E'
            ]);
        } else {
            return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 401);
        }
    }
 
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        //Request is validated, do logout        
        try {
            JWTAuth::invalidate($token);
 
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
 
    public function get_user(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);
 
        $user = JWTAuth::authenticate($request->token);
 
        return response()->json(['user' => $user]);
    }

    public function change_password(Request $request) {
        if (!(Hash::check($request->get('current-password'), JWTAuth::user()->password))) {
            // The passwords matches
            return response()->json(['status' => 'false', 'message' => 'Your current password does not matches with the password.']);
        }

        if(strcmp($request->get('current-password'), $request->get('new-password')) == 0){
            // Current password and new password same
            return response()->json(['status' => 'false', 'message' => 'New Password cannot be same as your current password.']);
        }


        //Change Password
        $user = JWTAuth::user();
        $user->password = bcrypt($request->get('new-password'));
        $user->save();

        return response()->json(['status' => true, 'message' => 'Password successfully changed!']);
    }

    public function authenticate_admin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['success' => false,'message' => $validator->messages()], 200);
        }

        //Request is validated
        //Crean token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                $data['title'] = 'Login Page';
                $data['template'] = 'admin';
                return view('index',[
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                    'data' => $data
                ]);
            }
        } catch (JWTException $e) {
            $data['title'] = 'Login Page';
            $data['template'] = 'admin';
            return view('index',[
                    'success' => false,
                    'message' => 'Could not create token.',
                    'data' => $data
                ]);
        }

            $user = auth()->user();
            if($user['role'] != 'user'){
                $request->session()->put('accessToken',json_encode(auth()->user()));
                return redirect('/dashboard');
            } else {
                $data['title'] = 'Login Page';
                $data['template'] = 'admin';
                return view('index',[
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                    'data' => $data
                ]);
            }
    }

    public function logout_admin(Request $request) {
        $request->session()->flush();
        return redirect('/');
    }
}