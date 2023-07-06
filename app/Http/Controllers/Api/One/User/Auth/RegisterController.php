<?php

namespace App\Http\Controllers\Api\One\User\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Controllers\Api\One\User\Controller;
use App\Http\Requests\User\RegisterBusinessRequest;

class RegisterController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('guest')->only(
            'register'
        );
    }

    /**
     * Create a new user account.
     */
    public function register(RegisterRequest $request)
    {
        $request['tag'] = $this->generateUserTag($request->first_name, $request->last_name);
        $request['artisan_type'] = "Individual";
        $referrer = null;
        if(isset($request->referrer_id)){
            $referrer = User::where('tag', $request->referrer_id)->first();
            $request['referrer_id'] = $referrer->id;
        }

        $auth = app('firebase.auth');
        $userProperties = [
            'password' => $request['password'],
            'displayName' => $request['first_name'].' '.$request['last_name'],
            'disabled' => false,
        ];

        if($request['email'] != null){
            $userProperties['email'] = $request['email'];
            $userProperties['emailVerified'] = false;
        }
        if($request['phone'] != null){
            $userProperties['phoneNumber'] = '+'.$request['phone'];
        }
        
        $createdUser = $auth->createUser($userProperties);

        $request['fb_id'] = $createdUser->uid;

        event(new Registered($new_user = User::create(
            $request->except([
            'accept_terms'
            ])
        )));

        if(!is_null($referrer)){
            $referrer->referees()->create([
                'referree_id' => $new_user->id
            ]); 
        }
        

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.'
        ], 201);
    }

    /**
     * Create a new business account.
     */
    public function register_business(RegisterBusinessRequest $request)
    {
        $request['business'] = 1;
        $request['artisan_type'] = "Business";
        $request['last_name'] = $request['business_name'];
        $request['first_name'] = $request['rep_name'];
        if(isset($request->referrer_id)){
            $request['referrer_id'] = User::where('tag', $request->referrer_id)->get()[0]->id;
        }
        $request['tag'] = $this->generateBusinessTag($request->business_name);
        
        $auth = app('firebase.auth');
        $userProperties = [
            'password' => $request['password'],
            'displayName' => $request['first_name'].' '.$request['last_name'],
            'disabled' => false,
        ];

        if($request['email'] != null){
            $userProperties['email'] = $request['email'];
            $userProperties['emailVerified'] = false;
        }
        if($request['phone'] != null){
            $userProperties['phoneNumber'] = '+'.$request['phone'];
        }
        
        $createdUser = $auth->createUser($userProperties);

        $request['fb_id'] = $createdUser->uid;
        
        event(new Registered(User::create($request->except([
            'accept_terms'
        ]))));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.'
        ], 201);
    }

    public static function generateUserTag($first_name, $last_name){
        $tag = substr($first_name, 0, 3).substr($last_name, 0, 3).substr(Str::uuid(), 0, 5).strval(rand(1000,9999));
        $tag = Str::lower($tag);
        while(User::where('tag', $tag)->count() > 0){
            $tag = \App\Http\Controllers\Api\One\User\Auth\RegisterController::generateUserTag($first_name, $last_name);
        }
        return $tag;
    }
    public static function generateBusinessTag($business_name){
        $tag = substr($business_name, 0, 3).substr(Str::uuid(), 0, 5).strval(rand(1000,9999));
        $tag = Str::lower($tag);
        while(User::where('tag', $tag)->count() > 0){
            $tag = \App\Http\Controllers\Api\One\User\Auth\RegisterController::generateBusinessTag($business_name);
        }
        return $tag;
    }

}
